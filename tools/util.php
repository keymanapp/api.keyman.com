<?php
  require_once('base.inc.php');

  $is_json_response = false;

  function fail($s, $code=400, $debug_log=null) {
    global $is_json_response;
    global $log;

    if(php_sapi_name() == 'cli') {
      if(!empty($log)) {
        echo $log . "\n";
      }
      echo $s;
      if(!empty($debug_log)) {
        echo "\n" . $debug_log . "\n";
      }
      exit(99);
    }

    header("HTTP/1.0 $code Failure");
    if($is_json_response) {
      $error = array("message" => $s);
      /*if(!empty($log)) {
        $error["log"] = $log;
      }*/
      json_print($error);
    } else {
      if(!empty($log)) {
        echo $log . "\n";
      }
      echo $s;
    }

    exit;
  }

  function text_response() {
    ini_set('html_errors', 0);
    header('Content-Type: text/plain; charset=utf-8');
  }

  function json_response() {
    global $is_json_response;
    $is_json_response = true;
    ini_set('html_errors', 0);
    header('Content-Type: application/json; charset=utf-8');
  }

  function javascript_response() {
    ini_set('html_errors', 0);
    global $is_json_response;
    $is_json_response = true;
    header('Content-Type: application/javascript; charset=utf-8');
  }

  function json_print($json) {
    echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
  }

  function allow_cors() {
    header('Access-Control-Allow-Origin: *');
  }
