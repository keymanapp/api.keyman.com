<?php
  /**
   * https://api.keyman.com/windows/{majorVersion}/update?version={fullVersion}&package_{id}={packageVersion}...&update={update}&manual={manual}
   *
   * Parameters:
   *  - `majorVersion` is the same as the Keyman release version, "14.0" or
   *    later
   *  - `fullVersion` is the currently installed version on the user's computer,
   *    e.g. "18.0.240.0"
   *  - `id` is package id, e.g. "khmer_angkor"; more than one package is
   *    allowed
   *  - `packageVersion` is the version of that package, e.g. "1.0"
   *  - `update` should be 0 for a new install (from setup.exe), or 1 for an
   *    update
   *  - `manual` should be 0 for automatic update checks, 1 for new install
   *    (from setup.exe), or where the user presses 'Check for Updates'
   *
   *  This returns a JSON object, following the schema at
   *  /schemas/windows-update/17.0/windows-update.json See example at
   *  /schemas/windows-update/17.0/sample.json
   *
   *  The value for `update` is included in the URL for keyboard package
   *  download in the JSON, e.g.:
   *
   *  `https://keyman.com/go/package/download/sil_ipa?version=2.0.2&platform=windows&tier=stable&update=0`
   *
   *  This is so it can be passed into the increment-download API, to allow us
   *  to track whether updates are from new installs or from existing users.
   */
  require_once __DIR__ . '/../../../../tools/base.inc.php';
  require_once __DIR__ . '/../../../../tools/util.php';
  require_once __DIR__ . '/../../../../tools/db/db.php';
  require_once __DIR__ . '/WindowsUpdateCheck.php';

  json_response();
  allow_cors();

  if(!isset($_REQUEST['version'])) {
    /* Invalid update check */
    fail('Invalid Parameters - expected version parameter', 401);
  }

  $tier = isset($_REQUEST['tier']) ? $_REQUEST['tier'] : 'stable';

  $isUpdate = empty($_REQUEST['update']) ? 0 : 1;
  $isManual = empty($_REQUEST['manual']) ? 0 : 1;

  $packages = [];
  foreach ($_REQUEST as $id => $version) {
    while(is_array($version)) {
      $version = array_shift($version);
    }

    if(substr($id, 0, 8) == 'package_')	{
      $PackageID = iconv("CP1252", "UTF-8", substr($id, 8, strlen($id)));
      $packages[$PackageID] = $version;
    } else if(substr($id, 0, 10) == 'packageid_')	{
      // PHP has a 'feature' where it silently converts space, period and other characters in
      // incoming parameter names into underscores. So we need to pass these in parameter
      // values instead of names. It would be nice if the tools didn't get in our way!
      $PackageID = iconv("CP1252", "UTF-8", $version);
      $pvid = 'packageversion_'.substr($id, 10);
      if(isset($_REQUEST[$pvid])) {
        $packages[$PackageID] = $_REQUEST[$pvid];
      }
    }
  }

  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  $u = new Keyman\Site\com\keyman\api\WindowsUpdateCheck();
  echo $u->execute($mssql, $tier, $_REQUEST['version'], $packages, $isUpdate, $isManual);
