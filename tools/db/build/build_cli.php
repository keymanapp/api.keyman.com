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

  $dci = new \DatabaseConnectionInfo();
  $schema = $dci->getInactiveSchema();

  $DBDataSources = new DBDataSources();

  $B = new BuildCJKTableClass();
  try {
    $B->BuildDatabase($DBDataSources, $schema, count($argv) > 1 && $argv[1] == '-f');
    $B->BuildCJKTables($DBDataSources, $schema, count($argv) > 1 && $argv[1] == '-f');
    $B->reportTime();
    build_log("Success");

    $dci->setActiveSchema($schema);
  } catch(Exception $e) {
    fail($e->getMessage());
  }
  //echo $log;
?>