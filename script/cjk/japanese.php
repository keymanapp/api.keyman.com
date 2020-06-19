<?php
  require_once('../../tools/util.php');

  allow_cors();
  javascript_response();

  require_once('../../tools/db/db.php');

  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  if(isset($_GET['kana'])) {
    $kana=$_GET["kana"]; $id = 0;
    if(isset($_GET['id'])) $id=$_GET["id"];

    if(($stmt = $mssql->prepare('
      SELECT DISTINCT kanji, gloss, pri FROM kmw_japanese WHERE (kana=?) ORDER BY pri
    ')) === false) {
      fail("Failed to prepare query: {$mysql->error}\n");
    }

    $stmt->bindParam(1, $kana);
    if($stmt->execute()) {
      $data = $stmt->fetchAll(PDO::FETCH_NUM);

      $t = ''; $u = '';
      $tt = '[';

      foreach($data as $row) {
        $kanji = $row[0];
        $gloss = $row[1];
        $gloss = str_replace("'","\'",$gloss);
        $t = $t . $tt . "'" . $kanji . "'";
        $u = $u . $tt . "'" . $gloss . "'";
        $tt = ',';
      }
      if($t == '') $t = '[]'; else $t .= ']';
      if($u == '') $u = '[]'; else $u .= ']';
    }

    if(($stmt = $mssql->prepare('
      SELECT DISTINCT TOP 20 kanji, gloss, pri FROM kmw_japanese WHERE ((kana LIKE ?) AND (kana <> ?)) ORDER BY pri
    ')) === false) {
      fail("Failed to prepare query: {$mysql->error}\n");
    }

    $kanalike = $kana.'%';
    $stmt->bindParam(1, $kanalike);
    $stmt->bindParam(2, $kana);
    if($stmt->execute()) {
      $data = $stmt->fetchAll(PDO::FETCH_NUM);

      $t1 = ''; $u1 = '';
      $tt = '[';
      foreach($data as $row) {
        $kanji = $row[0];
        $gloss = $row[1];
        $gloss = str_replace("'","\'",$gloss);
        $t1 = $t1 . $tt . "'" . $kanji . "'";
        $u1 = $u1 . $tt . "'" . $gloss . "'";
        $tt = ',';
      }
      if($t1 == '') $t1 = '[]'; else $t1 .= ']';
      if($u1 == '') $u1 = '[]'; else $u1 .= ']';
    }

    echo "Keyboard_japanese_obj.showCandidates(" . $id . ",'$kana'," . $t . "," . $t1 . "," . $u . "," . $u1 . ");";
  }
