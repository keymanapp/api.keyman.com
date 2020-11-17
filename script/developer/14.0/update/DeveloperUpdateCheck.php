<?php
  declare(strict_types=1);

  namespace Keyman\Site\com\keyman\api;

  require_once __DIR__ . '/../../../../tools/autoload.php';

  use Keyman\Site\com\keyman\api\DownloadsApi;
  use Keyman\Site\Common\KeymanHosts;

  class DeveloperUpdateCheck {
    const SETUP_REGEX = '/^keymandeveloper-.+\.exe/';

    private $isManual;
    private $currentTime; // used mainly for unit testing

    public function execute($mssql, $tier, $appVersion, $isManual, $currentTime = null) {

      $this->mssql = $mssql;
      $this->currentTime = $currentTime;

      $isUpdate = empty($isUpdate) ? 0 : 1;
      $this->isManual = !empty($isManual);

      $developer_update = [];
      $developer_update['developer'] = $this->BuildKeymanDesktopVersionResponse($tier, $appVersion, self::SETUP_REGEX);

      return json_encode($developer_update, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function BuildKeymanDesktopVersionResponse($tier, $InstalledVersion, $regex) {
      if(empty($this->DownloadVersions)) {
        $this->DownloadVersions = DownloadsApi::Instance()->GetPlatformVersion("developer");
        if($this->DownloadVersions === NULL) {
          fail('Unable to download or decode version data from '.KeymanHosts::Instance()->downloads_keyman_com, 500);
        }

        if(!isset($this->DownloadVersions->developer)) {
          fail("Unable to find {developer} key in ".KeymanHosts::Instance()->downloads_keyman_com." data", 500);
        }
      }

      // Check each of the tiers for the one that matches the major version.
      // This gets us upgrades on alpha, beta and stable tiers.
      $tiers = get_object_vars($this->DownloadVersions->developer);

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

          $filedata->url = KeymanHosts::Instance()->downloads_keyman_com . "/developer/$tier/{$filedata->version}/{$file}";
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
  }
