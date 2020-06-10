<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests {
  require_once(__DIR__ . '/../tools/base.inc.php');
  require_once(__DIR__ . '/../script/package-version/package-version.inc.php');
  require_once(__DIR__ . '/TestUtils.inc.php');
  require_once(__DIR__ . '/TestDBBuild.inc.php');

    use Keyman\Site\com\keyman\api\Tools\DB\DBConnect;
    use PHPUnit\Framework\TestCase;
  use Swaggest\JsonSchema\Schema;
  use Swaggest\JsonSchema\Context;

  final class PackageVersionTest extends TestCase
  {
    private const SchemaFilename = "/package-version/1.0/package-version.json";

    static function setUpBeforeClass(): void
    {
      TestDBBuild::Build();
    }

    public function testSimpleResultValidatesAgainstSchema(): void
    {
      $schema = TestUtils::LoadJSONSchema(PackageVersionTest::SchemaFilename);
      $mssql = \Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

      $pv = new \Keyman\Site\com\keyman\api\PackageVersion();
      $json = $pv->execute($mssql, [ 'keyboard' => ['khmer_angkor', 'bar'], ['model' => 'zoo','nrc.en.mtnt'] ], 'windows');

      // TODO(lowpri): find a way to skip this by emitting clean JSON object from execute()
      $json = json_decode(json_encode($json));

      // This will throw an exception if it does not pass
      $schema->in($json);

      // Once we get here we know this test has passed so make PHPUnit happy
      $this->assertTrue(true);
    }
  }
}
