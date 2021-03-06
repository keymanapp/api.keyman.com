<?php
  require_once('util.php');

  require_once(__DIR__ . '/../_common/KeymanHosts.php');
  require_once(__DIR__ . '/2020/DownloadsApi.php');
  class keymanversion
  {
    private $versionJsonFilename;

    function __construct()
    {
      $this->versionJsonFilename = dirname(__FILE__) . '/../.data/versions.json';
    }


    function getVersion($platform, $tier)
    {
      $json = NULL;


      if(\Keyman\Site\Common\KeymanHosts::Instance()->Tier() == \Keyman\Site\Common\KeymanHosts::TIER_TEST)
        // For tests, never use cached file.
        $json = FALSE;
      else
        // Get from cached file first. Otherwise using downloads.keyman.com
        $json = @file_get_contents($this->versionJsonFilename);

      if ($json === FALSE) {
        $json = \Keyman\Site\com\keyman\api\DownloadsApi::Instance()->GetPlatformVersion10();
      } else {
        $json = \Keyman\Site\com\keyman\api\DownloadsApi::Instance()->remove_utf8_bom($json);
        $json = json_decode($json);
      }

      if ($json === NULL) return NULL;

      if (property_exists($json, $platform)) {
        $json = $json->$platform;
        if ($json !== NULL && property_exists($json, $tier)) {
          return $json->$tier;
        }
      }

      return NULL;
    }

    function recache()
    {
      $json = \Keyman\Site\com\keyman\api\DownloadsApi::Instance()->GetPlatformVersion10();
      if ($json !== NULL) {
        file_put_contents($this->versionJsonFilename, $json);
      }
    }
  }
?>