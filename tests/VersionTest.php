<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
require_once(__DIR__ . '/../script/version/version.inc.php');
require_once(__DIR__ . '/ApiTestCase.php');
require_once(__DIR__ . '/TestUtils.inc.php');
require_once(__DIR__ . '/TestDBBuild.inc.php');

final class VersionTest extends ApiTestCase
{
  private const SchemaFilename = "/version/2.0/version.json";

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    $schema = TestUtils::LoadJSONSchema(VersionTest::SchemaFilename);

    $Version = new \Keyman\Site\com\keyman\api\Version();
    $platform = 'windows';
    $level = 'stable';
    $ver = $Version->execute($platform, $level);
    $this->assertNotEmpty($ver);

    // TODO(lowpri): find a way to skip this by emitting clean JSON object from execute()
    $ver = json_decode(json_encode($ver));

    // This will throw an exception if it does not pass
    $schema->in($ver);

    // Once we get here we know this test has passed so make PHPUnit happy
    $this->assertTrue(true);
  }
}