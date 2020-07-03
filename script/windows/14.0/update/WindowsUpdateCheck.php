<?php
  namespace Keyman\Site\com\keyman\api;

  require_once __DIR__ . '/../../../../tools/util.php';

  class WindowsUpdateCheck {
    const MSI_REGEX = '/^keyman(desktop)?\.msi$/';
    const BOOTSTRAP_REGEX = '/^setup\.exe$/';
    const BUNDLE_REGEX = '/^keyman(desktop)?-.+\.exe/';

    public function execute($tier, $appVersion, $packages) {
      $desktop_update = [];

      $desktop_update['msi'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::MSI_REGEX);
      $desktop_update['setup'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::BOOTSTRAP_REGEX);
      $desktop_update['bundle'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::BUNDLE_REGEX);
      if(!empty($desktop_update['bundle'])) {
        $newAppVersion = $desktop_update['bundle']->version;
      } else {
        $newAppVersion = $appVersion;
      }
      $desktop_update['keyboards'] = $this->BuildKeyboardsResponse($newAppVersion, $packages);

      return json_encode($desktop_update, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function BuildKeymanDesktopVersionResponse($tier, $InstalledVersion, $regex) {
      if(empty($this->DownloadVersions)) {
        $this->DownloadVersions = @file_get_contents(get_site_url_downloads() . "/api/version/windows/2.0");
        if($this->DownloadVersions === FALSE) {
          fail('Unable to retrieve version data from '.get_site_url_downloads(), 500);
        }
        $this->DownloadVersions = @json_decode($this->DownloadVersions);
        if($this->DownloadVersions === NULL) {
          fail('Unable to decode version data from '.get_site_url_downloads(), 500);
        }

        if(!isset($this->DownloadVersions->windows)) {
          fail("Unable to find {windows} key in ".get_site_url_downloads()." data", 500);
        }
      }

      // Check each of the tiers for the one that matches the major version.
      // This gets us upgrades on alpha, beta and stable tiers.
      $tiers = get_object_vars($this->DownloadVersions->windows);

      return $this->CheckVersionResponse($tier, $tiers, $InstalledVersion, $regex);
    }

    private function CheckVersionResponse($tier, $tiers, $InstalledVersion, $regex) {
      if(!isset($tiers[$tier])) return FALSE;
      $tierdata = $tiers[$tier];

      // We will support staying on alpha or beta tier once a version
      // hits stable. This is correct, with major upgrade support, as
      // that'll mean the user will transition to a new version on the
      // same tier (i.e. always staying bleeding edge)

      $files = get_object_vars($tierdata->files);
      foreach($files as $file => $filedata) {
        // This is currently tied to Windows -- for other platforms we need to change this
        if(preg_match($regex, $file)) {
          $filedata->url = get_site_url_downloads() . "/windows/$tier/{$filedata->version}/{$file}";
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

    private function BuildKeyboardsResponse($appVersion, $packages) {
      $keyboards = [];

      // For each keyboard in the parameter request, check for a new version or
      // for a keyboard that replaces it

      foreach ($packages as $id => $version) {
        $keyboard = $this->BuildKeyboardResponse($id, $version, $appVersion);
        if($keyboard !== FALSE) {
          $keyboards[$id] = $keyboard;
        }
      }
      return $keyboards;
    }

    private function BuildKeyboardResponse($id, $version, $appVersion) {
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

      if(!isset($KeyboardDownload->platformSupport->windows) || $KeyboardDownload->platformSupport->windows == 'none') {
        // Doesn't run on Windows / "windows" (this could in theory happen with a deprecation keyboard)
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
