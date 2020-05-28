<?php
  require_once(__DIR__ . '/../../base.inc.php');

  require_once(__DIR__ . '/build.php');
  require_once(__DIR__ . '/cjk/build.php');

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


  $mssqldb = $activedb->get_swap();

  BuildDatabase($mssqldb, count($argv) > 1 && $argv[1] == '-f');
  BuildCJKTables($mssqldb, count($argv) > 1 && $argv[1] == '-f');
  reportTime();
  build_log("Success");

  $activedb->set($mssqldb);

  //echo $log;
?>