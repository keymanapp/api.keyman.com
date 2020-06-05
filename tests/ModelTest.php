<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
require_once(__DIR__ . '/../script/model/model.inc.php');
require_once(__DIR__ . '/TestUtils.inc.php');
require_once(__DIR__ . '/TestDBBuild.inc.php');

use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
  private const SchemaFilename = "/model_info.distribution/1.0/model_info.distribution.json";

  static function setUpBeforeClass(): void
  {
    TestDBBuild::Build();
  }

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    $schema = TestUtils::LoadJSONSchema(ModelTest::SchemaFilename);
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