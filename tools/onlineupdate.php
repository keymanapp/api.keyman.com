<?php
  require_once('util.php');
  require_once(__DIR__ . '/2020/KeymanHosts.php');

  //TODO: LogToGoogleAnalytics()....

  class OnlineUpdate {
    private $platform, $installerRegex;

    public function __construct($platform, $installerRegex) {
      $this->platform = $platform;
      $this->installerRegex = $installerRegex;
    }

    public function execute() {
      json_response();
      allow_cors();

      if(!isset($_REQUEST['Version'])) {
        /* Invalid update check */
        fail('Invalid Parameters - expected Version parameter', 401);
      }

      /* Valid update check */
      $appVersion = $_REQUEST['Version'];

      $desktop_update = [];

      $desktop_update[$this->platform] = $this->BuildKeymanDesktopVersionResponse($appVersion);
      if(!empty($desktop_update[$this->platform])) {
        $newAppVersion = $desktop_update[$this->platform]->version;
      } else {
        $newAppVersion = $appVersion;
      }
      $desktop_update['keyboards'] = $this->BuildKeyboardsResponse($newAppVersion);

      echo json_encode($desktop_update, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function BuildKeymanDesktopVersionResponse($InstalledVersion) {
      $platform = $this->platform;

      // TODO: use DownloadsApi
      $DownloadVersions = @file_get_contents(\Keyman\Site\com\keyman\api\KeymanHosts::Instance()->downloads_keyman_com . "/api/version/$this->platform/2.0");
      if($DownloadVersions === FALSE) {
        fail('Unable to retrieve version data from '.\Keyman\Site\com\keyman\api\KeymanHosts::Instance()->downloads_keyman_com, 500);
      }
      $DownloadVersions = @json_decode($DownloadVersions);
      if($DownloadVersions === NULL) {
        fail('Unable to decode version data from '.\Keyman\Site\com\keyman\api\KeymanHosts::Instance()->downloads_keyman_com, 500);
      }

      if(!isset($DownloadVersions->$platform)) {
        fail("Unable to find {$platform} key in ".\Keyman\Site\com\keyman\api\KeymanHosts::Instance()->downloads_keyman_com." data", 500);
      }

      // Check each of the tiers for the one that matches the major version.
      // This gets us upgrades on alpha, beta and stable tiers.
      $tiers = get_object_vars($DownloadVersions->$platform);

      $match = $this->CheckVersionResponse('stable', $tiers, $platform, $InstalledVersion);
      if($match === FALSE)
        $match = $this->CheckVersionResponse('beta', $tiers, $platform, $InstalledVersion);
      if($match === FALSE)
        $match = $this->CheckVersionResponse('alpha', $tiers, $platform, $InstalledVersion);
      return $match;
    }

    private function CheckVersionResponse($tier, $tiers, $platform, $InstalledVersion) {
      if(!isset($tiers[$tier])) return FALSE;
      $tierdata = $tiers[$tier];
      if($this->IsSameMajorVersion($tierdata->version, $InstalledVersion)) {
        // TODO: Offer upgrades for MAJOR.x.x.x versions.
        // We still don't support staying on alpha or beta tier once a version
        // hits stable. We need to review the upgrade strategies for these.
        // Once a version is older than latest stable, we also don't offer updates for it;
        // this is probably also wrong.

        $files = get_object_vars($tierdata->files);
        foreach($files as $file => $filedata) {
          // This is currently tied to Windows -- for other platforms we need to change this
          if(preg_match($this->installerRegex, $file)) {
            $filedata->url = \Keyman\Site\com\keyman\api\KeymanHosts::Instance()->downloads_keyman_com . "/$platform/$tier/{$filedata->version}/{$file}";
            return $filedata;
          }
        }
      }
      return FALSE;
    }

    private function IsSameMajorVersion($v1, $v2) {
      if(empty($v1) || empty($v2)) return FALSE;
      $v1 = explode('.', $v1);
      $v2 = explode('.', $v2);
      return $v1[0] == $v2[0];
    }

    private function BuildKeyboardsResponse($appVersion) {
      $keyboards = [];

      // For each keyboard in the parameter request, check for a new version or
      // for a keyboard that replaces it

      foreach ($_REQUEST as $id => $version) {
        while(is_array($version)) {
          $version = array_shift($version);
        }

        if(substr($id, 0, 8) == 'Package_')	{
          $PackageID = iconv("CP1252", "UTF-8", substr($id, 8, strlen($id)));
          $keyboard = $this->BuildKeyboardResponse($PackageID, $version, $appVersion);
          if($keyboard !== FALSE) {
            $keyboards[$PackageID] = $keyboard;
          }
        }
      }
      return $keyboards;
    }

    private function BuildKeyboardResponse($id, $version, $appVersion) {
      $platform = $this->platform;
      $KeyboardDownload = @file_get_contents(\Keyman\Site\com\keyman\api\KeymanHosts::Instance()->api_keyman_com."/keyboard/$id");
      if($KeyboardDownload === FALSE) {
        // not found
        return FALSE;
      }
      $KeyboardDownload = @json_decode($KeyboardDownload);
      if($KeyboardDownload === NULL) {
        // invalid json
        return FALSE;
      }

      // Check if the keyboard has been replaced by something else and return it if so
      if(isset($KeyboardDownload->related)) {
        $r = get_object_vars($KeyboardDownload->related);
        foreach($r as $rid => $data) {
          if(isset($data->deprecatedBy) && $data->deprecatedBy) {
            $newData = $this->BuildKeyboardResponse($rid, '0.0', $appVersion); // 0.0 because we want to get the newest version
            if($newData === FALSE) {
              // Don't attempt to upgrade if the deprecating keyboard
              // is not available for some reason
              break;
            }
            return $newData;
          }
        }
      }

      if(!isset($KeyboardDownload->version)) {
        // Invalid keyboard data
        return FALSE;
      }

      if(version_compare($KeyboardDownload->version, $version, '<=')) {
        // User already a newer version of the keyboard installed
        return FALSE;
      }

      if(!isset($KeyboardDownload->platformSupport->$platform) || $KeyboardDownload->platformSupport->$platform == 'none') {
        // Doesn't run on Windows / "$platform" (this could in theory happen with a deprecation keyboard)
        return FALSE;
      }

      if(isset($KeyboardDownload->minKeymanVersion) && version_compare($KeyboardDownload->minKeymanVersion, $appVersion, '>')) {
        // New version of the keyboard doesn't run with the user's Keyman Desktop version
        return FALSE;
      }

      $KeyboardDownload->url = $this->BuildKeyboardDownloadPath($KeyboardDownload->id, $KeyboardDownload->version);
      if($KeyboardDownload->url === FALSE) {
        // Unable to build a url for the keyboard, would only happen if downloads.keyman.com was out of sync with
        // api.keyman.com
        return FALSE;
      }
      return $KeyboardDownload;
    }

    private function BuildKeyboardDownloadPath($id, $version) {
      // TODO: use DownloadsApi
      $data = @file_get_contents(\Keyman\Site\com\keyman\api\KeymanHosts::Instance()->downloads_keyman_com . "/api/keyboard/$id");
      if($data === FALSE) {
        return FALSE;
      }
      $data = @json_decode($data);
      if($data === NULL) {
        return FALSE;
      }
      if(!isset($data->kmp)) {
        return FALSE;
      }
      return $data->kmp;
    }
  }
?>