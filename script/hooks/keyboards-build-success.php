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

  $env = getenv();
  if($_REQUEST['token'] != $env['api_keyman_com_webhook_token']) {
    fail('Invalid token');
  }

  $log = "Triggered database build for keymanapp/keyboards and keymanapp/lexical-models";

  // This triggers the continuous webjob in App_Data/WebJobs/continuous/database_build
  file_put_contents('../../.data/MUST_REBUILD', "must rebuild");

  if($format === 'application/json') {
    echo json_encode(array("log" => $log));
  } else {
    echo $log;
  }
?>