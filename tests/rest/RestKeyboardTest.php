<?php declare(strict_types=1);
/*
 * Keyman is copyright (C) SIL Global. MIT License.
 *
 * Created by Marc Durdin on 2026-09-09
 *
 * Direct test on API endpoint /keyboard/<keyboard_id>
 */

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/RestTestCase.php');

final class RestKeyboardTest extends RestTestCase
{
  private const SchemaFilename = "/keyboard_info.distribution/1.0.6/keyboard_info.distribution.json";

  public function testBasicRequest(): void
  {
    $response = $this->http->request('GET', 'keyboard/newa_traditional');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaLinkedAndValidated($response, RestKeyboardTest::SchemaFilename);

    $json = json_decode($response->getBody()->getContents());

    // Check just a couple of fields for sanity
    $this->assertEquals("newa_traditional", $json->id);
    $this->assertEquals("1.1", $json->version);
  }

  public function testLegacyRequest(): void
  {
    $response = $this->http->request('GET', 'keyboard/armenian%20unicode');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaLinkedAndValidated($response, RestKeyboardTest::SchemaFilename);

    $json = json_decode($response->getBody()->getContents());

    // Check just a couple of fields for sanity
    $this->assertEquals("armenian unicode", $json->id);
    $this->assertEquals("1.0", $json->version);
  }
}