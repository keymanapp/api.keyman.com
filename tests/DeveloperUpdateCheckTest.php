<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
require_once(__DIR__ . '/../script/developer/14.0/update/DeveloperUpdateCheck.php');
require_once(__DIR__ . '/ApiTestCase.php');
require_once(__DIR__ . '/TestUtils.inc.php');

use Keyman\Site\com\keyman\api\tests\ApiTestCase;

final class DeveloperUpdateCheckTest extends ApiTestCase
{
  private const SchemaFilename = "/developer-update/14.0/developer-update.json";
  private $mssql, $schema;

  public function setUp(): void
  {
    $this->mssql = \Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();
    $this->schema = TestUtils::LoadJSONSchema(self::SchemaFilename);
  }

  // Note, in these tests, we send earlier version numbers in. This is fine as we
  // don't ever actually restrict this API to 14.0 or later versions, and we do so
  // in order to exercise major version upgrade scenarios.

  private function getJsonString($tier, $appVersion, $isManual, $currentTime = null)
  {
    $u = new \Keyman\Site\com\keyman\api\DeveloperUpdateCheck();
    $json = $u->execute($this->mssql, $tier, $appVersion, $isManual, $currentTime);
    $this->assertNotEmpty($json);
    $data = json_decode($json);
    $this->assertNotNull($data);

    // This will throw an exception if it does not pass
    $this->schema->in($data);

    return $json;
  }

  private function getJson($tier, $appVersion, $isManual, $currentTime = null)
  {
    return json_decode($this->getJsonString($tier, $appVersion, $isManual, $currentTime));
  }

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    $json = $this->getJsonString('alpha', '14.0.100', 1);
    $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/DeveloperUpdateCheck.14.0.json', $json);
  }

  public function testAlphaStaysInAlpha(): void
  {
    $json = $this->getJson('alpha', '13.0.100', 1);
    $this->assertEquals('alpha', $json->developer->stability);
    $this->assertEquals('14.0.181', $json->developer->version);
  }

  public function testBetaUpgradesToStable(): void
  {
    $json = $this->getJson('beta', '13.0.77', 1);
    var_dump($json);
    $this->assertEquals('stable', $json->developer->stability);
    $this->assertEquals('13.0.115.0', $json->developer->version);
  }

  public function testStableStaysInStable(): void
  {
    $json = $this->getJson('stable', '12.0.10', 1);
    $this->assertEquals('stable', $json->developer->stability);
    $this->assertEquals('13.0.115.0', $json->developer->version);
  }

  public function testStableGetsDelayedRollout(): void
  {
    // test 1 day after release
    // from fixture, 13.0.115.0 was released 2020-10-30
    // rollout schedule is defined in ReleaseSchedule and is based on minute of the hour
    // mktime is h:m:s, m/d/y (yuk!)

    // out-of-schedule
    $json = $this->getJson('stable', '12.0.10', 0, mktime(0, 20, 0, 10, 31, 2020));
    $this->assertFalse($json->developer, 'should not be offered release (1 day, 20 minutes)');

    // in-schedule
    $json = $this->getJson('stable', '12.0.10', 0, mktime(0, 1, 0, 10, 31, 2020));
    $this->assertEquals('13.0.115.0', $json->developer->version, 'should be offered release (1 day, 1 minute)');

    // minor update, out-of-schedule
    $json = $this->getJson('stable', '13.0.107.0', 0, mktime(0, 20, 0, 10, 31, 2020));
    $this->assertEquals('13.0.115.0', $json->developer->version, 'should be offered release');

    // test 30 days after release

    // in-schedule, 59 minutes after hour
    $json = $this->getJson('stable', '12.0.10', 0, mktime(0, 59, 0, 11, 28, 2020));
    $this->assertEquals('13.0.115.0', $json->developer->version, 'should be offered release');

    // in-schedule, 1 minute after hour
    $json = $this->getJson('stable', '12.0.10', 0, mktime(0, 1, 0, 11, 28, 2020));
    $this->assertEquals('13.0.115.0', $json->developer->version, 'should be offered release');

    // minor update, in-schedule
    $json = $this->getJson('stable', '13.0.107.0', 0, mktime(0, 59, 0, 11, 28, 2020));
    $this->assertEquals('13.0.115.0', $json->developer->version, 'minor update should be offered release');

    // out-of-schedule, major update, test manual check
    $json = $this->getJson('stable', '12.0.10', 1, mktime(0, 20, 0, 10, 31, 2020));
    $this->assertEquals('13.0.115.0', $json->developer->version, 'major update manual check should be offered release');
  }
}