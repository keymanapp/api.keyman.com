<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/../TestDBBuild.inc.php');
require_once(__DIR__ . '/../TestUtils.inc.php');
require_once(__DIR__ . '/RestTestCase.php');

use Keyman\Site\com\keyman\api\tests\TestUtils;

final class RestCloudTest extends RestTestCase
{
  private const SchemaFilename40 = "/keymanweb-cloud-api/keymanweb-cloud-api-4.0.json";
  private const SchemaFilename30 = "/keymanweb-cloud-api/keymanweb-cloud-api-3.0.json";
  private const SchemaFilename20 = "/keymanweb-cloud-api/keymanweb-cloud-api-2.0.json";
  private const SchemaFilename10 = "/keymanweb-cloud-api/keymanweb-cloud-api-1.0.json";

  static function setUpBeforeClass(): void
  {
    \Keyman\Site\com\keyman\api\tests\TestDBBuild::Build();
  }

  private function assertJsonSchemaAndEquals($response, $schemaFilename, $fixture) {
    // Now validate against the schema file
    $schema = TestUtils::LoadJSONSchema($schemaFilename);

    $stream = $response->getBody();
    $this->assertNotFalse($stream);
    $content = $stream->getContents();

    $json = json_decode($content);
    $this->assertNotNull($json);

    // This will throw an exception if it does not pass
    $schema->in($json);

    // We want a strict test of content here just to make sure we are breaking things
    $this->assertJsonStringEqualsJsonFile(__DIR__ . "/../fixtures/cloud/$fixture", $content);
  }

  // Version 4.0

  public function testSimple40Keyboard(): void
  {
    $response = $this->http->request('GET','cloud/4.0/keyboards?languageidtype=bcp47&version=13.0&keyboardid=sil_uganda_tanzania');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename40, '4.0-keyboard-sil_uganda_tanzania.json');
  }

  public function testSimple40Language(): void
  {
    $response = $this->http->request('GET','cloud/4.0/languages/am');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename40, '4.0-language-am.json');
  }

  public function test40LanguageKeyboard(): void
  {
    $response = $this->http->request('GET','cloud/4.0/languages/ti/gff_tir_et_7');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename40, '4.0-language-ti-gff_tir_et_7.json');
  }

  public function test40KeyboardLanguage(): void
  {
    $response = $this->http->request('GET','cloud/4.0/keyboards/gff_tir_et_7/ti');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename40, '4.0-keyboard-gff_tir_et_7-ti.json');
  }

  // Version 3.0

  public function testSimple30Keyboard(): void
  {
    $response = $this->http->request('GET','cloud/3.0/keyboards/us');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename30, '3.0-keyboard-us.json');
  }

  public function testSimple30Language(): void
  {
    $response = $this->http->request('GET','cloud/3.0/languages/khm');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename30, '3.0-language-khm.json');
  }

  public function test30LanguageKeyboard(): void
  {
    $response = $this->http->request('GET','cloud/3.0/languages/en/european');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename30, '3.0-language-en-european.json');
  }

  public function test30KeyboardLanguage(): void
  {
    $response = $this->http->request('GET','cloud/3.0/keyboards/european/en');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename30, '3.0-keyboard-european-en.json');
  }

  // Version 2.0

  public function testSimple20Keyboard(): void
  {
    // Note, the MySQL implementation has a bug with cloud/2.0/keyboards/ta which should return 404 but doesn't!
    // See _invalid_2.0-keyboard-ta.json
    // The MSSQL implementation is correct
    $response = $this->http->request('GET','cloud/2.0/keyboards/tamil');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename20, '2.0-keyboard-tamil.json');
  }

  public function testSimple20Language(): void
  {
    $response = $this->http->request('GET','cloud/2.0/languages/ta');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename20, '2.0-language-ta.json');
  }

  // Version 1.0

  public function testSimple10Keyboard(): void
  {
    $response = $this->http->request('GET','cloud/1.0/keyboards/chinese');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename10, '1.0-keyboard-chinese.json');
  }

  public function testSimple10Language(): void
  {
    $response = $this->http->request('GET','cloud/1.0/languages/th');
    $this->assertStandardJsonRestResponses($response);
    $this->assertJsonSchemaAndEquals($response, RestCloudTest::SchemaFilename10, '1.0-language-th.json');
  }

}