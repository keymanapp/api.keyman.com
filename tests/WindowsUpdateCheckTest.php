<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
require_once(__DIR__ . '/../script/windows/14.0/update/WindowsUpdateCheck.php');
require_once(__DIR__ . '/ApiTestCase.php');
require_once(__DIR__ . '/TestUtils.inc.php');

use Keyman\Site\com\keyman\api\tests\ApiTestCase;

final class WindowsUpdateCheckTest extends ApiTestCase
{
  private const SchemaFilename = "/windows-update/14.0/windows-update.json";
  private $mssql, $schema;

  public function setUp(): void
  {
    $this->mssql = \Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();
    $this->schema = TestUtils::LoadJSONSchema(self::SchemaFilename);
  }

  // Note, in these tests, we send earlier version numbers in. This is fine as we
  // don't ever actually restrict this API to 14.0 or later versions, and we do so
  // in order to exercise major version upgrade scenarios.

  private function getJsonString($tier, $appVersion, $packages, $isUpdate, $isManual, $currentTime = null)
  {
    $u = new \Keyman\Site\com\keyman\api\WindowsUpdateCheck();
    $json = $u->execute($this->mssql, $tier, $appVersion, $packages, $isUpdate, $isManual, $currentTime);
    $this->assertNotEmpty($json);
    $data = json_decode($json);
    $this->assertNotNull($data);

    // This will throw an exception if it does not pass
    $this->schema->in($data);

    return $json;
  }

  private function getJson($tier, $appVersion, $packages, $isUpdate, $isManual, $currentTime = null)
  {
    return json_decode($this->getJsonString($tier, $appVersion, $packages, $isUpdate, $isManual, $currentTime));
  }

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    $json = $this->getJsonString('alpha', '14.0.100', ['khmer_angkor'=>'1.0.2', 'foo'=>'0.1'], 0, 1);
    $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/WindowsUpdateCheck.14.0.json', $json);
  }

  public function testAlphaStaysInAlpha(): void
  {
    $json = $this->getJson('alpha', '13.0.100', [], 0, 1);
    $this->assertEquals('alpha', $json->bundle->stability);
    $this->assertEquals('14.0.102.0', $json->bundle->version);
  }

  public function testBetaUpgradesToStable(): void
  {
    $json = $this->getJson('beta', '13.0.77', [], 0, 1);
    $this->assertEquals('stable', $json->bundle->stability);
    $this->assertEquals('13.0.109.0', $json->bundle->version);
  }

  public function testStableStaysInStable(): void
  {
    $json = $this->getJson('stable', '12.0.10', [], 0, 1);
    $this->assertEquals('stable', $json->bundle->stability);
    $this->assertEquals('13.0.109.0', $json->bundle->version);
  }

  public function testStableGetsDelayedRollout(): void
  {
    // test 1 day after release
    // from fixture, 13.0.109.0 was released 2020-06-05
    // rollout schedule is defined in ReleaseSchedule and is based on minute of the hour
    // mktime is h:m:s, m/d/y (yuk!)

    // out-of-schedule
    $json = $this->getJson('stable', '12.0.10', [], 0, 0, mktime(0, 20, 0, 6, 6, 2020));
    $this->assertFalse($json->bundle, 'should not be offered release');

    // in-schedule
    $json = $this->getJson('stable', '12.0.10', [], 0, 0, mktime(0, 1, 0, 6, 6, 2020));
    $this->assertEquals('13.0.109.0', $json->bundle->version, 'should be offered release');

    // minor update, out-of-schedule
    $json = $this->getJson('stable', '13.0.107.0', [], 0, 0, mktime(0, 20, 0, 6, 6, 2020));
    $this->assertEquals('13.0.109.0', $json->bundle->version, 'should be offered release');

    // test 30 days after release

    // in-schedule, 59 minutes after hour
    $json = $this->getJson('stable', '12.0.10', [], 0, 0, mktime(0, 59, 0, 7, 4, 2020));
    $this->assertEquals('13.0.109.0', $json->bundle->version, 'should be offered release');

    // in-schedule, 1 minute after hour
    $json = $this->getJson('stable', '12.0.10', [], 0, 0, mktime(0, 1, 0, 7, 4, 2020));
    $this->assertEquals('13.0.109.0', $json->bundle->version, 'should be offered release');

    // minor update, in-schedule
    $json = $this->getJson('stable', '13.0.107.0', [], 0, 0, mktime(0, 59, 0, 7, 4, 2020));
    $this->assertEquals('13.0.109.0', $json->bundle->version, 'minor update should be offered release');

    // out-of-schedule, major update, test manual check
    $json = $this->getJson('stable', '12.0.10', [], 0, 1, mktime(0, 20, 0, 6, 6, 2020));
    $this->assertEquals('13.0.109.0', $json->bundle->version, 'major update manual check should be offered release');
  }
}