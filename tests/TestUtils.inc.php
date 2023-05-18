<?php

  namespace Keyman\Site\com\keyman\api\tests;

  use Swaggest\JsonSchema\RemoteRefProvider;
  use Swaggest\JsonSchema\Schema;
  use Swaggest\JsonSchema\Context;

  class TestUtils {
    private const DefaultHostname = 'http://localhost';

    static function LoadJSONSchema($filename) {
      $options = new Context();
      $options->remoteRefProvider = new ResolveLocalSchemas();
      return Schema::import($filename, $options);
    }

    static function Hostname() {
      if(isset($_SERVER['api_keyman_com_host'])) {
        return $_SERVER['api_keyman_com_host'];
      }
      return TestUtils::DefaultHostname;
    }
  }

  class ResolveLocalSchemas implements RemoteRefProvider
  {
    private const SchemaRoot = __DIR__ . "/../schemas";
    public function getSchemaData($url)
    {
      if(!preg_match("/^\//", $url)) {
        // We don't know where this schema file comes from; it's not local
        return false;
      }

      if(!file_exists(ResolveLocalSchemas::SchemaRoot . $url)) {
        fwrite(STDERR, "Could not find schema file ". ResolveLocalSchemas::SchemaRoot . $url . "\n");
        return false;
      }

      if(($data = file_get_contents(ResolveLocalSchemas::SchemaRoot . $url)) === FALSE) {
        fwrite(STDERR, "Could not load schema file ". ResolveLocalSchemas::SchemaRoot . $url . "\n");
        return false;
      }

      $data = json_decode($data);
      if($data === NULL) {
        fwrite(STDERR, "Schema file ". ResolveLocalSchemas::SchemaRoot . $url . " was not valid JSON\n");
        return false;
      }

      return $data;
    }
  }