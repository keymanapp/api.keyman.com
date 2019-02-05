<?php
  // Download comparison files for old vs new apis and validate for differences.
  
  $params = array_map(function($x) { return $x; }, $argv);
  array_shift($params);
  
  if(sizeof($params) > 0 && ($params[0] == '-l' || $params[0] == '--local')) {
    array_shift($params);
    $api_root = "http://api.keyman.com.local";
  } else {
    $api_root = "https://api.keyman.com";
  }
    
  $rkw_root = "https://r.keymanweb.com";
  $datapath = dirname(__FILE__) . "/data";
  
  if(!file_exists($datapath)) {
    mkdir($datapath);
  }
  
  $api = [
    10 => "$api_root/script/legacy/legacy10.php",
    20 => "$api_root/script/legacy/legacy20.php",
    30 => "$api_root/script/legacy/legacy30.php",
    40 => "$api_root/script/legacy/legacy40.php"
  ];
  
  $rkw = [ 
    10 => "$rkw_root/api10/index.php",
    20 => "$rkw_root/api10/index20.php",
    30 => "$rkw_root/api10/index30.php",
    40 => "$rkw_root/api10/index40.php"
  ];

  $api_public = [
    10 => "$api_root/cloud/1.0",
    20 => "$api_root/cloud/2.0",
    30 => "$api_root/cloud/3.0",
    40 => "$api_root/cloud/4.0"
  ];
  
  $rkw_public = [ 
    10 => "$rkw_root/api/1.0",
    20 => "$rkw_root/api/2.0",
    30 => "$rkw_root/api/3.0",
    40 => "$rkw_root/api/4.0"
  ];

  if(sizeof($params) > 0) {
    $ver = $params;
  } else {
    $ver = ['10','20','30','40','android','jsonp'];
  }
  
  if(in_array('10', $ver)) {
    save(10, "context=keyboard", "keyboard");
    save(10, "context=language", "language");
    save(10, "context=language&languageid=crj", "language-crj");
    save(10, "context=keyboard&keyboardid=bengali", "keyboard-bengali");
  }
  if(in_array('20', $ver)) {  
    save(20, "context=keyboard", "keyboard");
    save(20, "context=language", "language");
    save(20, "context=language&languageid=crj", "language-crj");
    save(20, "context=keyboard&keyboardid=bengali", "keyboard-bengali");

    save(20, "context=keyboard&device=androidphone", "keyboard-android");
    save(20, "context=language&device=androidphone", "language-android");
    save(20, "context=keyboard&device=iphone", "keyboard-iphone");
    save(20, "context=language&device=iphone", "language-iphone");
  }  
  
  if(in_array('30', $ver)) {  
    save(30, "context=keyboard", "keyboard");
    save(30, "context=language", "language");
    save(30, "context=language&languageid=crj", "language-crj");
    save(30, "context=keyboard&keyboardid=bengali", "keyboard-bengali");

    save(30, "context=keyboard&device=androidphone", "keyboard-android");
    save(30, "context=language&device=androidphone", "language-android");
    save(30, "context=keyboard&device=iphone", "keyboard-iphone");
    save(30, "context=language&device=iphone", "language-iphone");
  }  
  
  if(in_array('40', $ver)) {  
    save(40, "context=keyboard", "keyboard");
    save(40, "context=language", "language");
    save(40, "context=language&languageid=crj", "language-crj");
    save(40, "context=keyboard&keyboardid=bengali", "keyboard-bengali");

    save(40, "context=keyboard&device=androidphone", "keyboard-android");
    save(40, "context=language&device=androidphone", "language-android");
    save(40, "context=keyboard&device=iphone", "keyboard-iphone");
    save(40, "context=language&device=iphone", "language-iphone");
  }
  
  if(in_array('jsonp', $ver)) {
    save(40, "context=keyboard&keyboardid=bengali&jsonp=keyman.register&timerid=5", "jsonp-single");
    save(40, "context=keyboard&jsonp=keyman.register&keyboardid=french,@heb,european2@nor,european2@swe&timerid=7", "jsonp-multiple");
  }  
  
  if(in_array('android', $ver)) {
    save_public(30, "languages/cim/european2?device=androidphone", "android-language-keyboard");
  }
  
  function json_save($url, $filename, $jsonp) {
    echo "Downloading $url\n   Save to: $filename\n";
    $data = file_get_contents($url);
    if($data === FALSE) die("failed to download $url");
    if($jsonp) {
      // we need to extract the text before the first { and the last }
      if(preg_match('/^([^{]+)(\{.+\})(\s*\);\s*)$/', $data, $matches) != 1) die("failed to decode jsonp");
      $data = $matches[2];
    }

    $json = json_decode($data);
    if($json === NULL) die('failed to decode json');
    $data = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if($data === FALSE) die('failed to encode json');
    
    if($jsonp) {
      $data = $matches[1] . "\n" . $data . "\n" . $matches[3];
    }
    
    file_put_contents("$filename", $data);
  }
  
  function save($ver, $query, $filename) {
    global $api, $rkw, $datapath;
    if(!file_exists("$datapath/$ver")) {
      mkdir("$datapath/$ver");
    }
    json_save("{$api[$ver]}?$query", "$datapath/$ver/$filename.api.json", strpos($filename, 'jsonp') !== FALSE);
    json_save("{$rkw[$ver]}?$query", "$datapath/$ver/$filename.rkw.json", strpos($filename, 'jsonp') !== FALSE);
  }  
  
  function save_public($ver, $query, $filename) {
    global $api_public, $rkw_public, $datapath;
    if(!file_exists("$datapath/$ver")) {
      mkdir("$datapath/$ver");
    }
    json_save("{$api_public[$ver]}/$query", "$datapath/$ver/$filename.api.json", strpos($filename, 'jsonp') !== FALSE);
    json_save("{$rkw_public[$ver]}/$query", "$datapath/$ver/$filename.rkw.json", strpos($filename, 'jsonp') !== FALSE);
  }  
  
?>