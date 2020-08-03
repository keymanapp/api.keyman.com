<?php
  // Webhook for keymanapp/keyboards build process
  // Whenever a build completes on keymanapp/keyboards/master, rebuild the keyboard database

  require_once('../../tools/util.php');
  require_once('../../tools/db/servervars.php');

  if(isset($_REQUEST['format'])) {
    $format = $_REQUEST['format'];
    if($format !== 'application/json' && $format !== 'text/plain') {
      $format = 'application/json';
    }
  } else {
    $format = 'application/json';
  }

  if($format === 'application/json') {
    json_response();
  } else {
    text_response();
  }

  if(!isset($_REQUEST['token'])) {
    fail('No token');
  }

  if($_REQUEST['token'] != $_SERVER['api_keyman_com_webhook_token']) {
    fail('Invalid token');
  }

  $log = "Triggered database build for keymanapp/keyboards and keymanapp/lexical-models\n".
         "=============================================================================\n\n";

  function build_log($message) {
    global $log;
    $log .= $message . "\n";
  }

  require_once('../../tools/db/build/build.inc.php');
  require_once('../../tools/db/build/cjk/build.inc.php');
  require_once('../../tools/db/build/datasources.inc.php');

  function Build() {
    $DBDataSources = new DBDataSources();
    $dci = new DatabaseConnectionInfo();
    $schema = $dci->getInactiveSchema();

    $B = new BuildCJKTableClass();
    $B->BuildDatabase($DBDataSources, $dci->getDatabase(), $schema, true);
    $B->BuildCJKTables($DBDataSources, $dci->getDatabase(), $schema, true);

    $dci->setActiveSchema($schema);

    global $format, $log;
    if($format === 'application/json') {
      echo json_encode(array("log" => $log));
    } else {
      echo $log;
    }
  }

  Build();
?>