<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
//require_once(__DIR__ . '/../script/search/search.inc.php');
require_once(__DIR__ . '/TestUtils.inc.php');
require_once(__DIR__ . '/TestDBBuild.inc.php');

use PHPUnit\Framework\TestCase;

final class KeyboardTest extends TestCase
{
  private const SchemaFilename = "/keyboard_info.distribution/1.0.6/keyboard_info.distribution.json";

  static function setUpBeforeClass(): void
  {
    TestDBBuild::Build();
  }

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    $schema = TestUtils::LoadJSONSchema(KeyboardTest::SchemaFilename);
    $mssql = \Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

    $m = new \Keyman\Site\com\keyman\api\Model();
    $json = $m->getModelJson($mssql, 'gff.am.gff_amharic');
    $this->assertNotNull($json);

    // This will throw an exception if it does not pass
    $schema->in($json);

    // Once we get here we know this test has passed so make PHPUnit happy
    $this->assertTrue(true);
  }
}