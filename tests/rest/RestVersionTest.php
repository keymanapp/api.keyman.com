<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/RestTestCase.php');

final class RestVersionTest extends RestTestCase
{
  private const SchemaFilename = "/version/2.0/version.json";

  public function testBasicRequest(): void
  {
    // NOTE: this test currently works against live data because we have not mocked the
    // version api yet. So we cannot test the result against known fixed values; we can
    // reliably assume there will *always* be a stable windows version, though!
    $response = $this->http->request('GET', 'version/windows/stable');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaLinkedAndValidated($response, RestVersionTest::SchemaFilename);

    $json = json_decode($response->getBody()->getContents());

    var_dump($json);

    // Now check the actual content, as far as we can
    $this->assertEquals("windows", $json->platform);
    $this->assertEquals("stable", $json->level);
    // The version string will be a.b.c or a.b.c.d (in the future we hope to use a.b.c only)
    $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+(\.\d)?$/', $json->version);
  }

  public function testLegacyRequest(): void
  {
    // Corresponds to the last legacy stable web version 2.0.473. This query should always return "473"
    // as there will never be a newer version with the legacy API.
    $LEGACY_WEB_STABLE_VERSION = 473;

    $response = $this->http->request('GET', 'version');
    $this->assertStandardTextRestResponses($response);
    $body = $response->getBody()->getContents();
    $this->assertEquals($LEGACY_WEB_STABLE_VERSION, $body);
  }
}