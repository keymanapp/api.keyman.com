<?php
  require_once('util.php');

  class keymanversion
  {
    private $versionJsonFilename, $downloadsApiVersionUrl;

    function __construct()
    {
      $this->versionJsonFilename = dirname(__FILE__) . '/../.data/versions.json';
      $this->downloadsApiVersionUrl = get_site_url_downloads() . '/api/version';
    }

    function remove_utf8_bom($text)
    {
      $bom = pack('H*', 'EFBBBF');
      $text = preg_replace("/^$bom/", '', $text);
      return $text;
    }

    function getVersion($platform, $tier)
    {
      $json = NULL;

      // Get from cached file first. Otherwise using downloads.keyman.com
      $json = @file_get_contents($this->versionJsonFilename);
      if ($json === NULL) {
        $json = @file_get_contents("{$this->downloadsApiVersionUrl}/$platform");
      }

      if ($json !== NULL && $json !== FALSE) {
        $json = $this->remove_utf8_bom($json);
        $json = json_decode($json);

        if ($json !== NULL && $json !== FALSE) {
          if (property_exists($json, $platform)) {
            $json = $json->$platform;
            if ($json !== NULL && property_exists($json, $tier)) {
              return $json->$tier;
            }
          }
        }
      }
      return null;
    }

    function recache()
    {
      $json = @file_get_contents($this->downloadsApiVersionUrl);
      if ($json !== NULL) {
        file_put_contents($this->versionJsonFilename, $json);
      }
    }
  }
?>