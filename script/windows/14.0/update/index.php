<?php
  require_once __DIR__ . '/../../../../tools/base.inc.php';
  require_once __DIR__ . '/WindowsUpdateCheck.php';

  json_response();
  allow_cors();

  if(!isset($_REQUEST['version'])) {
    /* Invalid update check */
    fail('Invalid Parameters - expected Version parameter', 401);
  }

  $tier = isset($_REQUEST['tier']) ? $_REQUEST['tier'] : 'stable';

  $packages = [];
  foreach ($_REQUEST as $id => $version) {
    while(is_array($version)) {
      $version = array_shift($version);
    }

    if(substr($id, 0, 8) == 'package_')	{
      $PackageID = iconv("CP1252", "UTF-8", substr($id, 8, strlen($id)));
      $packages[$PackageID] = $version;
    }
  }

  $u = new Keyman\Site\com\keyman\api\WindowsUpdateCheck();
  echo $u->execute($tier, $_REQUEST['version'], $packages);
