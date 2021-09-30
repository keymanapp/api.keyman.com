<?php
  require_once('util.php');

  require_once(__DIR__ . '/../_common/KeymanHosts.php');
  require_once(__DIR__ . '/2020/DownloadsApi.php');
  require_once(__DIR__ . '/2020/SKeymanComApi.php');
  class keymanversion
  {
    private $cachedSKeymanComVersion;
    private function getSKeymanComLatestVersion($baseVersion) {
      if(!isset($this->cachedSKeymanComVersion)) {
        $sjson = \Keyman\Site\com\keyman\api\SKeymanComApi::Instance()->GetKmwVersion();
        if(!$sjson || in_array($baseVersion, $sjson->versions)) {
          return $baseVersion;
        }
        $this->cachedSKeymanComVersion = $sjson;
      } else {
        $sjson = $this->cachedSKeymanComVersion;
      }

      // We need to find the next most recent version in the same major version
      // values returned from s.keyman.com are in reverse order, so the first one
      // we find will be the right one
      $parts = explode('.', $baseVersion);
      $major = $parts[0] . '.';
      foreach($sjson->versions as $version) {
        if(substr($version, 0, strlen($major)) == $major) {
          return $version;
        }
      }
      // Note: at this point, the version was not found, but there's not much we
      // can do because no earlier version was found either. We'll rely on
      // whatever downloads.keyman.com gives us
      return $baseVersion;
    }

    function getVersion($platform, $tier) {
      $json = \Keyman\Site\com\keyman\api\DownloadsApi::Instance()->GetPlatformVersion10();

      if ($json === NULL) return NULL;

      if (property_exists($json, $platform)) {
        $json = $json->$platform;
        if ($json !== NULL && property_exists($json, $tier)) {
          if($platform == 'web') {
            // We need to also check s.keyman.com versions
            return $this->getSKeymanComLatestVersion($json->$tier);
          }
          return $json->$tier;
        }
      }
      return NULL;
    }

    function getVersions($platform) {
      $json = \Keyman\Site\com\keyman\api\DownloadsApi::Instance()->GetPlatformVersion10();
      if($json === NULL) return NULL;
      if(!property_exists($json, $platform)) return NULL;
      $versions = $json->$platform;
      if($versions === NULL) return NULL;

      if($platform == 'web') {
        if(isset($versions->alpha)) $versions->alpha = $this->getSKeymanComLatestVersion($versions->alpha);
        if(isset($versions->beta)) $versions->beta = $this->getSKeymanComLatestVersion($versions->beta);
        if(isset($versions->stable)) $versions->stable = $this->getSKeymanComLatestVersion($versions->stable);
      }

      return $versions;
    }
  }
?>