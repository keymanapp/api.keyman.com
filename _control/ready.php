<?php
  header("Content-Type: text/plain");

  // Test db connection. Connect fails if db is not ready
  require_once(__DIR__ . '/../script/search/2.0/search.inc.php');
  require_once(__DIR__ . '/../tools/db/db.php');
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  // Test db is built
  $s = new KeyboardSearch($mssql);
  $json = $s->GetSearchMatches($platform='android', $query='khmer_angkor', $obsolete=FALSE, $pageNumber=1);
  json_print($json);

  // Test web server ready, and _common files, and vendor files ready
  if (!file_exists(__DIR__ . '/../tools/db/build/cjk/chinese_pinyin_import.sql') &&
      !file_exists(__DIR__ . '/../tools/db/build/cjk/japanese_import.sql')) {    
    die('/tools/db/build/cjk/*_import.sql not ready');
  }

  if (!file_exists(__DIR__ . '/../tools/db/activeschema.txt')) {
    die('/tools/db/activeschema.txt not ready');
  }

  if (!file_exists(__DIR__ . '/../_common/KeymanHosts.php')) {
    die('/_common not ready');
  }
  
  if (!is_dir(__DIR__ . '/../vendor')) {
    die('/vendor not ready');
  }

  echo "ready\n";
