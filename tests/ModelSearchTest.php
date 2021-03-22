<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
require_once(__DIR__ . '/../script/model-search/model-search.inc.php');
require_once(__DIR__ . '/TestUtils.inc.php');
require_once(__DIR__ . '/TestDBBuild.inc.php');

use PHPUnit\Framework\TestCase;

final class ModelSearchTest extends TestCase
{
  private const SchemaFilename = "/model-search/1.0.1/model-search.json";

  static function setUpBeforeClass(): void
  {
    TestDBBuild::Build();
  }

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    //http://api.keyman.com.local/model?q=sil
    $schema = TestUtils::LoadJSONSchema(ModelSearchTest::SchemaFilename);
    $mssql = \Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

    $m = new \Keyman\Site\com\keyman\api\ModelSearch();
    $json = $m->execute($mssql, 'sil');
    $this->assertNotNull($json);

    // This will throw an exception if it does not pass
    $schema->in($json);

    // Once we get here we know this test has passed so make PHPUnit happy
    $this->assertTrue(true);
  }
}