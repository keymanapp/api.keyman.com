<?php
  header("Content-Type: text/plain");

  require_once(__DIR__ . '/../tools/db/db.php');

  // Test web server ready, and _common files, and vendor files ready

  // Test db connection. Connect fails if db is not ready
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  // Test db is built
  $stmt = $mssql->prepare('EXEC sp_keyboard_search_by_id ?, ?');
  $id = 'khmer_angkor';
  $obsolete = FALSE;
  $stmt->bindParam(1, $id);
  $stmt->bindParam(2, $obsolete);
  try {
    if ($stmt->execute()) {
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      //json_print($data);
    }
  } catch(PDOException $e) {
    die('Error: ' . $e->getMessage());
  }

  // Test chinese_pinyin_import.sql ready with query
  $stmt = $mssql->prepare(
    'SELECT pinyin_key, chinese_text, tip FROM kmw_chinese_pinyin WHERE pinyin_key=? ORDER BY frequency DESC, id');
  $py = 'biguansuoguo';
  $stmt->bindParam(1, $py);
  try {
    if ($stmt->execute()) {
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      //json_print($data);
    }
  } catch(PDOException $e) {
    die('chinese_pinyin_import.sql not ready: ' . $e->getMessage());
  }

  // Test japanese_import.sql ready with query
  $stmt = $mssql->prepare(
    'SELECT DISTINCT kanji, gloss, pri FROM kmw_japanese WHERE (kana=?) ORDER BY pri');
  $kana = 'あいでし';
  $stmt->bindParam(1, $kana);
  try {
    if ($stmt->execute()) {
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      //json_print($data);
    }
  } catch(PDOException $e) {
    die('japanese_import.sql not ready: ' . $e->getMessage());
  }

  if (!file_exists(__DIR__ . '/../.data/activeschema.txt')) {
    die('/.data/activeschema.txt not ready');
  }

  if (!file_exists(__DIR__ . '/../_common/KeymanHosts.php')) {
    die('/_common not ready');
  }

  if (!is_dir(__DIR__ . '/../vendor')) {
    die('/vendor not ready');
  }

  echo "ready\n";
