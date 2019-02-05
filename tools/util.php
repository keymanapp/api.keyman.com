<?php
  $is_json_response = false;

  function fail($s, $code=400) {
    global $is_json_response, $mysql;
    global $log;
    header("HTTP/1.0 $code Fail: $s");
    if($is_json_response) {
      $error = array("message" => $s);
      /*if(isset($mysql)) { <-- enable this on test sites if you are debugging mysql issues
        $error['mysql'] = $mysql->error;
      }*/
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
    header('Content-Type: text/plain');
  }
  
  function json_response() {
    global $is_json_response;
    $is_json_response = true;
    header('Content-Type: application/json; charset=utf-8');
  }
  
  function javascript_response() {
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

?>