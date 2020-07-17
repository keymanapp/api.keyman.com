<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/../TestUtils.inc.php');
require_once(__DIR__ . '/../TestDBBuild.inc.php');

use     \Keyman\Site\com\keyman\api\tests\TestUtils;

final class RestSearch10Test extends RestTestCase
{
  private const SchemaFilename = "/search/1.0.2/search.json";

  static function setUpBeforeClass(): void
  {
    \Keyman\Site\com\keyman\api\tests\TestDBBuild::Build();
  }

  public function testSimpleSearchResultValidatesAgainstSchema(): void
  {
    $schema = TestUtils::LoadJSONSchema(RestSearch10Test::SchemaFilename);

    $response = $this->http->request('GET', '/search?q=thai');
    $this->assertStandardJsonRestResponses($response);
    $body = $response->getBody()->getContents();

    $json = json_decode($body);

    // This will throw an exception if it does not pass
    $schema->in($json);

    // Once we get here we know this test has passed so make PHPUnit happy
    $this->assertTrue(true);
  }

  public function testSimpleSearchResultContentsConsistent()
  {
    $response = $this->http->request('GET', '/search?q=khmer');
    $this->assertStandardJsonRestResponses($response);
    $body = $response->getBody()->getContents();
    // Note: some irrelevant differences were manually corrected, including sort of khmer_angkor vs khmer10, and lastModifiedDate values
    $this->assertJsonStringEqualsJsonFile(__DIR__ . '/../fixtures/Search.1.0.khmer.json', $body, "Search for 'khmer' gives same results as Search.1.0.khmer.json");
  }
}
