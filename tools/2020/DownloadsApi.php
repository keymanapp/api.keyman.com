<?php
  declare(strict_types=1);

  namespace Keyman\Site\com\keyman\api;

  use Keyman\Site\com\keyman\api\KeymanHosts;

  class DownloadsApi {
    public function GetPlatformVersion($platform) {
      $json = @file_get_contents(KeymanHosts::Instance()->downloads_keyman_com . "/api/version/$platform/2.0");
      if($json === FALSE) return NULL;
      return @json_decode($json);
    }

    public function GetKeyboardVersion($keyboard) {
      $data = @file_get_contents(KeymanHosts::Instance()->downloads_keyman_com . "/api/keyboard/$keyboard");
      if($data === FALSE) {
        return NULL;
      }
      return @json_decode($data);
    }
  }