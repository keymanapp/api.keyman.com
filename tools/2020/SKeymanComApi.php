<?php
  declare(strict_types=1);

  namespace Keyman\Site\com\keyman\api;

  use Keyman\Site\Common\KeymanHosts;

  class SKeymanComApi {
    private static ?SKeymanComApi $instance = NULL;

    public static function Instance() {
      if(self::$instance == NULL) {
        // Mocking entirely within PHPUnit is not possible for rest-based
        // tests; this could be refactored to a neater consistent pattern
        // in the future, probably.
        if(KeymanHosts::Instance()->Tier() == KeymanHosts::TIER_TEST) {
          self::$instance = new MockSKeymanComApi();
        } else {
          self::$instance = new SKeymanComApi();
        }
      }
      return self::$instance;
    }

    protected function GetData($path) {
      return @file_get_contents(KeymanHosts::Instance()->SERVER_s_keyman_com . "/api" . $path);
    }

    public function GetKmwVersion() {
      $json = $this->GetData("/kmwversion.php");
      if($json === FALSE) return NULL;
      $data = @json_decode($json);
      if(!is_object($data) || !is_array($data->versions)) return NULL;
      return $data;
    }
  }

  class MockSKeymanComApi extends SKeymanComApi {
    protected function GetData($path) {
      return file_get_contents(__DIR__ . "/../../tests/fixtures/s.keyman.com$path.json");
    }
  }