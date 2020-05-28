<?php
  require_once('base.inc.php');

  $is_json_response = false;

  function fail($s, $code=400) {
    global $is_json_response;
    global $log;
    header("HTTP/1.0 $code Failure");
    if($is_json_response) {
      $error = array("message" => $s);
      if(!empty($log)) {
        $error["log"] = $log;
      }
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
    header('Content-Type: text/plain');
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

  function get_site_url_api() {
    // We could do this algorithmically so that we can
    // support dev machines more easily, but that can
    // come later.
    return 'https://api.keyman.com';
  }

  function get_site_url_downloads() {
    return 'https://downloads.keyman.com';
  }

  function get_model_download_url($id, $version, $filename) {
    return get_site_url_downloads() . "/models/{$id}/{$version}/{$filename}";
  }
