<?php declare(strict_types=1);
/*
 * Keyman is copyright (C) SIL Global. MIT License.
 *
 * Created by Marc Durdin on 2026-09-09
 *
 * Direct test on API endpoint /increment-download/<keyboard_id>
 */

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/RestTestCase.php');

final class RestIncrementDownloadTest extends RestTestCase
{
  private const SchemaFilename = "/increment-download/1.0/increment-download.json";

  public function testIncrementRequest(): void
  {
    //
    // Check format of response and cache the current increment
    //

    $response = $this->http->request('POST', 'increment-download/newa_traditional', [
      'form_params' => ['key' => 'local']
    ]);
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaLinkedAndValidated($response, RestIncrementDownloadTest::SchemaFilename, true);

    $json = json_decode($response->getBody()->getContents());
    $this->assertEquals("newa_traditional", $json->keyboard_id);

    // cache current count
    $currentCount = $json->count;

    //
    // Call a second time to check the increment is actually working
    //

    $response = $this->http->request('POST', 'increment-download/newa_traditional', [
      'form_params' => ['key' => 'local']
    ]);
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaLinkedAndValidated($response, RestIncrementDownloadTest::SchemaFilename, true);

    $json = json_decode($response->getBody()->getContents());
    $this->assertEquals("newa_traditional", $json->keyboard_id);

    // Verify that the counter is incrementing
    $this->assertEquals($currentCount + 1, $json->count);
  }

  public function testLegacyIncrementRequest(): void
  {
    //
    // Check format of response and cache the current increment
    //

    $response = $this->http->request('POST', 'increment-download/armenian%20unicode', [
      'form_params' => ['key' => 'local']
    ]);
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaLinkedAndValidated($response, RestIncrementDownloadTest::SchemaFilename, true);

    $json = json_decode($response->getBody()->getContents());
    $this->assertEquals("armenian unicode", $json->keyboard_id);

    // cache current count
    $currentCount = $json->count;

    //
    // Call a second time to check the increment is actually working
    //

    $response = $this->http->request('POST', 'increment-download/armenian%20unicode', [
      'form_params' => ['key' => 'local']
    ]);
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaLinkedAndValidated($response, RestIncrementDownloadTest::SchemaFilename, true);

    $json = json_decode($response->getBody()->getContents());
    $this->assertEquals("armenian unicode", $json->keyboard_id);

    // Verify that the counter is incrementing
    $this->assertEquals($currentCount + 1, $json->count);
  }
}