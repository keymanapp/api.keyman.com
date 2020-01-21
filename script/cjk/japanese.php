<?php
  require_once('../../tools/db/db.php');
  require_once('../../tools/util.php');

  allow_cors();
  javascript_response();

  if(isset($_GET['kana'])) {
    $kana=$_GET["kana"]; $id = 0;
    if(isset($_GET['id'])) $id=$_GET["id"];

    if(($stmt = $mysql->prepare('
      SELECT DISTINCT kanji, gloss, pri FROM cjk.kmw_japanese WHERE (kana=?) ORDER BY pri
    ')) === false) {
      fail("Failed to prepare query: {$mysql->error}\n");
    }

    $stmt->bind_param("s", $kana);
    if($stmt->execute()) {
      $result = $stmt->get_result();
      $data = $result->fetch_all();

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

    if(($stmt = $mysql->prepare('
      SELECT DISTINCT kanji, gloss, pri FROM cjk.kmw_japanese WHERE ((kana LIKE ?) AND (kana <> ?)) ORDER BY pri LIMIT 20
    ')) === false) {
      fail("Failed to prepare query: {$mysql->error}\n");
    }

    $kanalike = $kana.'%';
    $stmt->bind_param("ss", $kanalike, $kana);
    if($stmt->execute()) {
      $result = $stmt->get_result();
      $data = $result->fetch_all();

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

    $mysql->close();

    echo "Keyboard_japanese_obj.showCandidates(" . $id . ",'$kana'," . $t . "," . $t1 . "," . $u . "," . $u1 . ");";
  }
