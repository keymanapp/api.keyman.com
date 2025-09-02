<?php
  declare(strict_types=1);

  namespace Keyman\Site\com\keyman\api;

  require_once __DIR__ . '/../../../../tools/autoload.php';
  require_once __DIR__ . '/../../../keyboard/keyboard.inc.php'; // TODO this class needs to be moved to an autoload location

  use Keyman\Site\com\keyman\api\DownloadsApi;
  use Keyman\Site\Common\KeymanHosts;

  class WindowsUpdateCheck {
    const MSI_REGEX = '/^keyman(desktop)?\.msi$/';
    const BOOTSTRAP_REGEX = '/^setup\.exe$/';
    const BUNDLE_REGEX = '/^keyman(desktop)?-.+\.exe/';

    private $isManual;
    private $currentTime; // used mainly for unit testing

    public function execute($mssql, $tier, $appVersion, $packages, $isUpdate, $isManual, $currentTime = null) {

      $this->mssql = $mssql;
      $this->currentTime = $currentTime;
      $isUpdate = empty($isUpdate) ? 0 : 1;
      $this->isManual = !empty($isManual);

      $desktop_update = [];

      $desktop_update['msi'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::MSI_REGEX);
      $desktop_update['setup'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::BOOTSTRAP_REGEX);
      
      $desktop_update['bundle'] = $this->RepairVersionCheck($appVersion);
      if(!$desktop_update['bundle']) {
        $desktop_update['bundle'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::BUNDLE_REGEX);
      }

      if(!empty($desktop_update['bundle'])) {
        $newAppVersion = $desktop_update['bundle']->version;
      } else {
        $newAppVersion = $appVersion;
      }
      $desktop_update['keyboards'] = $this->BuildKeyboardsResponse($tier, $newAppVersion, $packages, $isUpdate);

      return json_encode($desktop_update, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    // This is function is added to repair an issue where Keyman windows would not upgrade
    // to a newer version correctly see the issues below.
    // https://github.com/keymanapp/keyman/issues/13831
    // https://github.com/keymanapp/keyman/pull/13867
    // https://github.com/keymanapp/keyman/pull/14010
    // https://github.com/keymanapp/keyman/issues/14586
    // https://github.com/keymanapp/api.keyman.com/issues/293 
    private function RepairVersionCheck($InstalledVersion) {

      $installedParts = explode('.', $InstalledVersion);
      if($installedParts[0] == '18' && version_compare($InstalledVersion, '18.0.236', '<=')) {
      
        $repairVersionObj = new \stdClass();
        $repairVersionObj->name = "Keyman for Windows";
        $repairVersionObj->version = "18.0.240";
        $repairVersionObj->date = "2025-08-27";
        $repairVersionObj->platform = "win";
        $repairVersionObj->stability = "stable";
        $repairVersionObj->file = "keyman-18.0.000.exe";
        $repairVersionObj->md5 = "9E58343C8E5820676C52B148EFECBEB7";
        $repairVersionObj->type = "exe";
        $repairVersionObj->build = "240";
        $repairVersionObj->size = 111440328;
        $repairVersionObj->url = KeymanHosts::Instance()->downloads_keyman_com . "/windows/stable/repair-14586/18.0.000.exe";
        return $repairVersionObj;
      }
      return null;
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

      // We will support staying on alpha tier even once a version
      // hits stable. This is correct, with major upgrade support, as
      // that'll mean the user will transition to a new version on the
      // same tier (i.e. always staying bleeding edge). However, for beta
      // users, the assumption will be that they should upgrade to the stable
      // release once it is available, as the beta branch will go stale and
      // they will receive no further updates on it otherwise (until next beta
      // period, anyway).
      switch($tier) {
        case 'alpha':
          return $this->CheckVersionResponse($tier, $tiers, $InstalledVersion, $regex);
        case 'beta':
          $response = $this->CheckVersionResponse('stable', $tiers, $InstalledVersion, $regex);
          if($response === FALSE || version_compare($response->version, $InstalledVersion, '<'))
            $response = $this->CheckVersionResponse($tier, $tiers, $InstalledVersion, $regex);
          return $response;
        case 'stable':
          return $this->CheckVersionResponse($tier, $tiers, $InstalledVersion, $regex);
        default:
          fail('Unexpected tier '.$tier, 500);
      }
    }
    
    private function CheckVersionResponse($tier, $tiers, $InstalledVersion, $regex) {
      if(!isset($tiers[$tier])) return FALSE;
      $tierdata = $tiers[$tier];
      if(is_array($tierdata->files)) return FALSE;
      
      $files = get_object_vars($tierdata->files);
      foreach($files as $file => $filedata) {
        // This is currently tied to Windows -- for other platforms we need to change this
        if(preg_match($regex, $file)) {

          if(!$this->isManual &&
              $tier == 'stable' &&
              !$this->IsSameMajorVersion($InstalledVersion, $tierdata->version)) {
            // We're going to stagger upgrades by the minute of the hour for the check, to
            // ensure we don't have everyone major-update at once and potentially cause us
            // grief. This will mean that we need additional PRs to update this value; that
            // gives us tracking automatically, so I'm good with that.
            if(!ReleaseSchedule::DoesRequestMeetSchedule($filedata->date, $this->currentTime)) {
              return FALSE;
            }
          }

          $filedata->url = KeymanHosts::Instance()->downloads_keyman_com . "/windows/$tier/{$filedata->version}/{$file}";
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
      if(sizeof($keyboards) == 0) return new \stdClass();
      return $keyboards;
    }

    private function BuildKeyboardResponse($tier, $id, $version, $appVersion, $isUpdate) {
      $KeyboardDownload = Keyboard::execute($this->mssql, $id);

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
