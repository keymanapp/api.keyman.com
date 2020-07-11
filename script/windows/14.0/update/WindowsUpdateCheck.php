<?php
  declare(strict_types=1);

  namespace Keyman\Site\com\keyman\api;

  require_once __DIR__ . '/../../../../tools/autoload.php';

  use Keyman\Site\com\keyman\api\DownloadsApi;

  class WindowsUpdateCheck {
    const MSI_REGEX = '/^keyman(desktop)?\.msi$/';
    const BOOTSTRAP_REGEX = '/^setup\.exe$/';
    const BUNDLE_REGEX = '/^keyman(desktop)?-.+\.exe/';

    public function execute($tier, $appVersion, $packages, $isUpdate) {

      $isUpdate = empty($isUpdate) ? 0 : 1;

      $desktop_update = [];

      $desktop_update['msi'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::MSI_REGEX);
      $desktop_update['setup'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::BOOTSTRAP_REGEX);
      $desktop_update['bundle'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::BUNDLE_REGEX);
      if(!empty($desktop_update['bundle'])) {
        $newAppVersion = $desktop_update['bundle']->version;
      } else {
        $newAppVersion = $appVersion;
      }
      $desktop_update['keyboards'] = $this->BuildKeyboardsResponse($tier, $newAppVersion, $packages, $isUpdate);

      return json_encode($desktop_update, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function BuildKeymanDesktopVersionResponse($tier, $InstalledVersion, $regex) {
      if(empty($this->DownloadVersions)) {
        $this->DownloadVersions = DownloadsApi::Instance()->GetPlatformVersion("windows");
        if($this->DownloadVersions === NULL) {
          fail('Unable to download or decode version data from '.KeymanHosts::Instance()->downloads_keyman_com, 500);
        }

        if(!isset($this->DownloadVersions->windows)) {
          fail("Unable to find {windows} key in ".KeymanHosts::Instance()->downloads_keyman_com." data", 500);
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
          $filedata->url = KeymanHosts::Instance()->downloads_keyman_com . "/windows/$tier/{$filedata->version}/{$file}";
          return $filedata;
        }
      }

      return FALSE;
    }

    private function BuildKeyboardsResponse($tier, $appVersion, $packages, $isUpdate) {
      $keyboards = [];

      // For each keyboard in the parameter request, check for a new version or
      // for a keyboard that replaces it

      foreach ($packages as $id => $version) {
        $keyboard = $this->BuildKeyboardResponse($tier, $id, $version, $appVersion, $isUpdate);
        if($keyboard !== FALSE) {
          $keyboards[$id] = $keyboard;
        }
      }
      return $keyboards;
    }

    private function BuildKeyboardResponse($tier, $id, $version, $appVersion, $isUpdate) {
      // TODO: this should use the class instead of file_get_contents
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
            $newData = $this->BuildKeyboardResponse($tier, $rid, '0.0', $appVersion, $isUpdate); // 0.0 because we want to get the newest version
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

      $KeyboardDownload->url = $this->BuildKeyboardDownloadPath($KeyboardDownload->id, $KeyboardDownload->version, $tier, $isUpdate);
      if($KeyboardDownload->url === FALSE) {
        // Unable to build a url for the keyboard, would only happen if downloads.keyman.com was out of sync with
        // api.keyman.com
        return FALSE;
      }
      return $KeyboardDownload;
    }

    private function BuildKeyboardDownloadPath($id, $version, $tier, $isUpdate) {
      $data = DownloadsApi::Instance()->GetKeyboardVersion($id);
      if($data === NULL) {
        return FALSE;
      }
      if(!isset($data->kmp)) {
        return FALSE;
      }
      return KeymanHosts::Instance()->keyman_com . "/go/package/download/" .
        rawurlencode($id) .
        "?version=" . rawurlencode($version) .
        "&platform=windows" .
        "&tier=$tier" .
        "&update=$isUpdate";
    }
  }
