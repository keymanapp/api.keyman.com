<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/../ApiTestCase.php');
require_once(__DIR__ . '/../TestUtils.inc.php');

use Keyman\Site\com\keyman\api\tests\TestUtils;
use Keyman\Site\com\keyman\api\tests\ApiTestCase;
use Keyman\Site\Common\KeymanHosts;
use GuzzleHttp;

class RestTestCase extends ApiTestCase
{
  protected $http;

  public function setUp(): void
  {
    $this->http = new GuzzleHttp\Client(['base_uri' => TestUtils::Hostname().'/']);
  }

  public function tearDown(): void
  {
    $this->http = null;
  }

  protected function assertStandardTextRestResponses($response): void
  {
    $this->assertEquals(200, $response->getStatusCode());

    $headers = $response->getHeaders();

    $contentType = $headers["Content-Type"];
    $this->assertCount(1, $contentType);
    $this->assertEquals("text/plain; charset=utf-8", $contentType[0]);

    $cors = $headers["Access-Control-Allow-Origin"];
    $this->assertCount(1, $cors);
    $this->assertEquals("*", $cors[0]);
  }

  protected function assertStandardJavascriptRestResponses($response): void
  {
    $this->assertEquals(200, $response->getStatusCode());

    $headers = $response->getHeaders();

    $contentType = $headers["Content-Type"];
    $this->assertCount(1, $contentType);
    $this->assertEquals("application/javascript; charset=utf-8", $contentType[0]);

    $cors = $headers["Access-Control-Allow-Origin"];
    $this->assertCount(1, $cors);
    $this->assertEquals("*", $cors[0]);
  }

  protected function assertStandardJsonRestResponses($response): void
  {
    $this->assertEquals(200, $response->getStatusCode());

    $headers = $response->getHeaders();

    $contentType = $headers["Content-Type"];
    $this->assertCount(1, $contentType);
    $this->assertEquals("application/json; charset=utf-8", $contentType[0]);

    $cors = $headers["Access-Control-Allow-Origin"];
    $this->assertCount(1, $cors);
    $this->assertEquals("*", $cors[0]);
  }

  protected function assertJsonSchemaLinkedAndValidated($response, $schema): void
  {
    $headers = $response->getHeaders();

    // The caller will pass in a versioned schema filename; we want the unversioned equivalent to check the Link header
    $base_schema = basename($schema);
    $links = $headers["Link"];
    $this->assertCount(1, $links); // we should only have one Link header, the JSON schema
    $this->assertEquals("<" . KeymanHosts::Instance()->api_keyman_com . "/schemas/$base_schema#>; rel=\"describedby\"" , $links[0]);

    // Now validate against the versioned schema file
    $schema = TestUtils::LoadJSONSchema($schema);

    $json = $response->getBody();
    $this->assertNotFalse($json);

    // This will throw an exception if it does not pass
    $schema->in($json);
  }
}