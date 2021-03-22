<?php
  require_once __DIR__ . '/../../../../tools/base.inc.php';
  require_once __DIR__ . '/../../../../tools/util.php';
  require_once __DIR__ . '/../../../../tools/db/db.php';
  require_once __DIR__ . '/DeveloperUpdateCheck.php';

  json_response();
  allow_cors();

  if(!isset($_REQUEST['version'])) {
    /* Invalid update check */
    fail('Invalid Parameters - expected version parameter', 401);
  }

  $tier = isset($_REQUEST['tier']) ? $_REQUEST['tier'] : 'stable';

  $isManual = empty($_REQUEST['manual']) ? 0 : 1;

  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  $u = new Keyman\Site\com\keyman\api\DeveloperUpdateCheck();
  echo $u->execute($mssql, $tier, $_REQUEST['version'], $isManual);
