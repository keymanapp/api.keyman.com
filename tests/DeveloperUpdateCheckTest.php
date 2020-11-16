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

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    $u = new \Keyman\Site\com\keyman\api\DeveloperUpdateCheck();
    $json = $u->execute($this->mssql, 'alpha', '14.0.100', 1);

    $this->assertNotEmpty($json);
    $data = json_decode($json);
    $this->assertNotNull($data);

    // This will throw an exception if it does not pass
    $this->schema->in($data);

    // TODO: add some more realism!
    $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/DeveloperUpdateCheck.14.0.json', $json);
  }
}