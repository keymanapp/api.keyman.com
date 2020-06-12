<?php
  require_once('../../tools/util.php');

  allow_cors();
  javascript_response();

  require_once('../../tools/db/db.php');

  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  if(isset($_GET['py'])) {
    $py=$_GET["py"]; $id = 0;
    if(isset($_GET['id'])) $id=$_GET["id"];

    if(($stmt = $mssql->prepare('
      SELECT pinyin_key, chinese_text, tip FROM kmw_chinese_pinyin WHERE pinyin_key=? ORDER BY frequency DESC
    ')) === false) {
      fail("Failed to prepare query: {$mssql->errorInfo()}\n");
    }

    $pylike = $py.'%';
    $stmt->bindParam(1, $py);

    if($stmt->execute()) {
      $data = $stmt->fetchAll(PDO::FETCH_NUM);

      $t = ''; $u = '';
      $tt = '[';

      foreach($data as $row) {
        $t = $t . $tt . "'" . $row[1] . "'";
        $u = $u . $tt . "'" . $row[2] . "'";
        $tt = ',';
      }
      if($t == '') $t = '[]'; else $t .= ']';
      if($u == '') $u = '[]'; else $u .= ']';
    }

    if(($stmt = $mssql->prepare('
      SELECT TOP 20 pinyin_key, chinese_text, tip FROM kmw_chinese_pinyin WHERE pinyin_key LIKE ? AND pinyin_key <> ? ORDER BY frequency DESC
    ')) === false) {
      fail("Failed to prepare query #2: {$mysql->error}\n");
    }

    $stmt->bindParam(1, $pylike);
    $stmt->bindParam(2, $py);
    if($stmt->execute()) {
      $data = $stmt->fetchAll(PDO::FETCH_NUM);

      $t1 = ''; $u1 = '';
      $tt = '[';

      foreach($data as $row) {
        $t1 = $t1 . $tt . "'" . $row[1] . "'";
        $u1 = $u1 . $tt . "'" . $row[2] . "'";
        $tt = ',';
      }
      if($t1 == '') $t1 = '[]'; else $t1 .= ']';
      if($u1 == '') $u1 = '[]'; else $u1 .= ']';
    }

    echo "Keyboard_chinese_obj.showCandidates(" . $id . ",'$py'," . $t . "," . $t1 . "," . $u . "," . $u1 . ");";
  }
