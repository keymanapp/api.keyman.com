<?php
  declare(strict_types=1);

  namespace Keyman\Site\com\keyman\api;

  use Keyman\Site\com\keyman\api\KeymanHosts;

  class DownloadsApi {
    private static ?DownloadsApi $instance = NULL;

    public static function Instance() {
      if(self::$instance == NULL) {
        // Mocking entirely within PHPUnit is not possible for rest-based
        // tests; this could be refactored to a neater consistent pattern
        // in the future, probably.
        if(KeymanHosts::Instance()->Tier() == KeymanHosts::TIER_TEST) {
          self::$instance = new MockDownloadsApi();
        } else {
          self::$instance = new DownloadsApi();
        }
      }
      return self::$instance;
    }

    protected function GetData($path) {
      return @file_get_contents(KeymanHosts::Instance()->downloads_keyman_com . "/api" . $path);
    }

    public function GetPlatformVersion($platform) {
      $json = $this->GetData("/version/$platform/2.0");
      if($json === FALSE) return NULL;
      return @json_decode($json);
    }

    public function GetKeyboardVersion($keyboard) {
      $data = $this->GetData("/keyboard/$keyboard");
      if($data === FALSE) return NULL;
      return @json_decode($data);
    }

    public function GetPlatformVersion10(?string $platform = null) {
      $data = $this->GetData("/version" . (empty($platform) ? "" : "/$platform"));
      if($data === FALSE) return NULL;
      $json = $this->remove_utf8_bom($data);
      return @json_decode($json);
    }

    public function remove_utf8_bom($text)
    {
      // TODO: This is probably no longer necessary
      $bom = pack('H*', 'EFBBBF');
      $text = preg_replace("/^$bom/", '', $text);
      return $text;
    }
  }

  class MockDownloadsApi extends DownloadsApi {
    protected function GetData($path) {
      return file_get_contents(__DIR__ . "/../../tests/fixtures/downloads.keyman.com$path.json");
    }
  }