<?php
  require_once(__DIR__ . '/../../base.inc.php');
  require_once(__DIR__ . '/datasources.inc.php');
  require_once(__DIR__ . '/build.inc.php');
  require_once(__DIR__ . '/cjk/build.inc.php');

  // CLI version of fail
  function fail($s) {
    echo $s;
    exit(1);
  }

  function build_log($message) {
    global $log;
    echo $message . "\n";
    $log .= $message . "\n";
  }

  $log = '';

  $activedb = new \ActiveDB();
  $mssqldb = $activedb->get_swap();

  $DBDataSources = new DBDataSources();

  try {
    BuildDatabase($DBDataSources, $mssqldb, count($argv) > 1 && $argv[1] == '-f');
    BuildCJKTables($DBDataSources, $mssqldb, count($argv) > 1 && $argv[1] == '-f');
    reportTime();
    build_log("Success");

    $activedb->set($mssqldb);
  } catch(Exception $e) {
    fail($e->getMessage());
  }
  //echo $log;
?>