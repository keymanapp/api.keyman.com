<?php
/*
 * Keyman is copyright (C) SIL Global. MIT License.
 *
 * Basic annual statistics for SIL reports
 */

  require_once(__DIR__ . '/../../tools/util.php');

  allow_cors();
  json_response();

  require_once(__DIR__ . '/../../tools/db/db.php');
  require_once(__DIR__ . '/annual-statistics.inc.php');
  require_once __DIR__ . '/../../tools/autoload.php';
  use Keyman\Site\Common\KeymanHosts;
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  if(!isset($_REQUEST['startDate']) || !isset($_REQUEST['endDate'])) {
    fail('startDate, endDate parameters must be set');
  }

  $startDate = $_REQUEST['startDate'];
  $endDate = $_REQUEST['endDate'];

  /**
   * https://api.keyman.com/script/statistics/annual.php
   */

  $stats = new \Keyman\Site\com\keyman\api\AnnualStatistics();
  $data = $stats->execute($mssql, $startDate, $endDate);
  json_print($data);
