<?php declare(strict_types=1);
/*
 * Keyman is copyright (C) SIL Global. MIT License.
 *
 * Created by Marc Durdin on 2026-09-09
 *
 * Direct test on API endpoint /model/<model_id>
 */

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/RestTestCase.php');

final class RestModelTest extends RestTestCase
{
  private const SchemaFilename = "/model_info.distribution/1.0.1/model_info.distribution.json";

  public function testBasicRequest(): void
  {
    $response = $this->http->request('GET', 'model/gff.am.gff_amharic');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaLinkedAndValidated($response, RestModelTest::SchemaFilename);

    $json = json_decode($response->getBody()->getContents());

    // Check just a couple of fields for sanity
    $this->assertEquals("gff.am.gff_amharic", $json->id);
    $this->assertEquals("1.0", $json->version);
  }
}