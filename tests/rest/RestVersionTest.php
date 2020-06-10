<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/../TestUtils.inc.php');

use Keyman\Site\com\keyman\api\tests\TestUtils;
use PHPUnit\Framework\TestCase;

final class RestVersionTest extends TestCase
{
  private const SchemaFilename = "/version/2.0/version.json";

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    $schema = TestUtils::LoadJSONSchema(RestVersionTest::SchemaFilename);

    $json = file_get_contents(TestUtils::Hostname() ."/version/windows/alpha");
    $this->assertNotFalse($json);

    $ver = json_decode($json);
    $this->assertNotNull($json);

    // This will throw an exception if it does not pass
    $schema->in($ver);

    // Once we get here we know this test has passed so make PHPUnit happy
    $this->assertTrue(true);
  }
}