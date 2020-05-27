<?php
  require_once('build.php');

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


  BuildCJKTables($activedb->get_swap(), count($argv) > 1 && $argv[1] == '-f');

  //echo $log;
?>