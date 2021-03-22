<?php
  require_once('util.php');

  use Keyman\Site\com\keyman\api\DownloadsApi;
  use Keyman\Site\com\keyman\api\ReleaseSchedule;
  use Keyman\Site\Common\KeymanHosts;

  /**
   * Provides update checks for Keyman Desktop for versions 10.0-13.0.
   * For version 14.0 and later, see class WindowsUpdateCheck. As we
   * are providing update checks only for older, stable versions in this
   * class, there is no need to do checks on tier.
   */
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

      $DownloadVersions = DownloadsApi::Instance()->GetPlatformVersion($platform);
      if($DownloadVersions === NULL) {
        fail('Unable to download version data from '.KeymanHosts::Instance()->downloads_keyman_com, 500);
      }

      if(!isset($DownloadVersions->$platform)) {
        fail("Unable to find {$platform} key in ".KeymanHosts::Instance()->downloads_keyman_com." data", 500);
      }

      // Check the stable tier only, as this class now only supports legacy versions of
      // Keyman Desktop and Keyman Developer.
      $tier = 'stable';
      $tiers = get_object_vars($DownloadVersions->$platform);
      if(!isset($tiers[$tier])) return FALSE;
      $tierdata = $tiers[$tier];

      // Keyman Desktop version 10.0 should not automatically update to 13.0, because 11.0-13.0
      // had a bug which broke keyboard registration details. Note, this is not yet fixed in 14.0
      // either -- see keymanapp/keyman#3865.
      if($platform == 'windows' && $this->IsSameMajorVersion($InstalledVersion, '10.0')) {
        // For now, we don't even attempt to locate a newer 10.0 release.
        return FALSE;
      }

      // Look for the file data that matches our requirements
      $files = get_object_vars($tierdata->files);
      foreach($files as $file => $filedata) {
        if(preg_match($this->installerRegex, $file)) {
          // We want to inject the final URL into the data returned from downloads.keyman.com
          $filedata->url = KeymanHosts::Instance()->downloads_keyman_com . "/$platform/$tier/{$filedata->version}/{$file}";

          if(!$this->IsSameMajorVersion($InstalledVersion, $tierdata->version)) {
            // We're going to stagger upgrades by the minute of the hour for the check, to
            // ensure we don't have everyone major-update at once and potentially cause us
            // grief.

            // For the initial rollout of this functionality, our stable release is 13.0, so
            // we want to manually set the release date to around the time this PR lands.
            $date = $this->IsSameMajorVersion('13.0', $tierdata->version) ? /*'2020-11-18'*/ '2020-01-01' : $filedata->date;

            if(!ReleaseSchedule::DoesRequestMeetSchedule($date)) {
              return FALSE;
            }
          }

          return $filedata;
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
      $KeyboardDownload = @file_get_contents(KeymanHosts::Instance()->api_keyman_com."/keyboard/$id");
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
      $data = @file_get_contents(KeymanHosts::Instance()->downloads_keyman_com . "/api/keyboard/$id");
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