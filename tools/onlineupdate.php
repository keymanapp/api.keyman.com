<?php
  require_once('util.php');

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

      $DownloadVersions = @file_get_contents(get_site_url_downloads() . "/api/version/$this->platform/2.0");
      if($DownloadVersions === FALSE) {
        fail('Unable to retrieve version data from '.get_site_url_downloads(), 500);
      }
      $DownloadVersions = @json_decode($DownloadVersions);
      if($DownloadVersions === NULL) {
        fail('Unable to decode version data from '.get_site_url_downloads(), 500);
      }

      if(!isset($DownloadVersions->$platform)) {
        fail("Unable to find {$platform} key in ".get_site_url_downloads()." data", 500);
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
          $filedata->url = get_site_url_downloads() . "/$platform/$tier/{$filedata->version}/{$file}";

          if(!$this->IsSameMajorVersion($InstalledVersion, $tierdata->version)) {
            // We're going to stagger upgrades by the minute of the hour for the check, to
            // ensure we don't have everyone major-update at once and potentially cause us
            // grief.

            // For the initial rollout of this functionality, our stable release is 13.0, so
            // we want to manually set the release date to around the time this PR lands.
            $date = $this->IsSameMajorVersion('13.0', $tierdata->version) ? '2020-11-19' : $filedata->date;

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
      $KeyboardDownload = @file_get_contents(get_site_url_api()."/keyboard/$id");
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
      $data = @file_get_contents(get_site_url_downloads() . "/api/keyboard/$id");
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

  // This is copied from staging, ReleaseSchedule.php; it is temporary until we
  // release Keyman 14.0 and transition staging -> production
  class ReleaseSchedule {

    /**
     * This function helps us to do a gradual roll out of a major upgrade of Keyman
     * on Windows, by testing the current time against both the date of the release
     * and the minute of the hour, so that users checking only at specific times
     * receive the update.
     *
     * @param string releaseDate    yyyy-mm-dd date that major version was released
     * @param int    currentTime    Unix timestamp of current time
     * @return bool  true if the request should be presented with the upgrade
     */
    public static function DoesRequestMeetSchedule($releaseDate, $currentTime = null) {
      // This is an arbitrary schedule; see for example Chrome's release schedule:
      // https://chromium.googlesource.com/chromium/src/+/master/docs/process/release_cycle.md
      $schedule = [
        5 => 3,   // 5 days for 3 minutes, 5% of user base
        10 => 6,  // 10 days for 6 minutes / 10%
        15 => 18, // 15 days for 18 minutes / 30%
        20 => 36  // 20 days for 36 minutes / 60%
      ];

      $releaseDate = new \DateTime($releaseDate);
      $currentDate = new \DateTime();
      if($currentTime) {
        $currentDate->setTimestamp($currentTime);
      }

      $currentTime = getdate($currentDate->getTimestamp());

      if($currentDate < $releaseDate) {
        // Don't match if current date before release date
        return FALSE;
      }

      $interval = $currentDate->diff($releaseDate);

      foreach($schedule as $days => $minutes) {
        if($interval->days <= $days) {
          return $currentTime['minutes'] < $minutes;
        }
      }

      // It's been more than 20 days so everyone gets the update now
      return TRUE;
    }
  }

?>