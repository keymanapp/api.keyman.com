<?php
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
