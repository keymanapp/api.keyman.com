<?php
  require_once('../../tools/util.php');

  allow_cors();
  javascript_response();

  require_once('../../tools/db/db.php');

  if(isset($_GET['py'])) {
    $py=$_GET["py"]; $id = 0;
    if(isset($_GET['id'])) $id=$_GET["id"];

    if(($stmt = $mysql->prepare('
      SELECT pinyin_key, chinese_text, tip FROM cjk.kmw_chinese_pinyin WHERE pinyin_key=? ORDER BY frequency DESC
    ')) === false) {
      fail("Failed to prepare query: {$mysql->error}\n");
    }

    $pylike = $py.'%';
    $stmt->bind_param("s", $py);

    if($stmt->execute()) {
      $result = $stmt->get_result();
      $data = $result->fetch_all();

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

    if(($stmt = $mysql->prepare('
      SELECT pinyin_key, chinese_text, tip FROM cjk.kmw_chinese_pinyin WHERE pinyin_key LIKE ? AND pinyin_key <> ? ORDER BY frequency DESC LIMIT 20
    ')) === false) {
      fail("Failed to prepare query #2: {$mysql->error}\n");
    }

    $stmt->bind_param("ss", $pylike, $py); //, $idlike, $id);
    if($stmt->execute()) {
      $result = $stmt->get_result();
      $data = $result->fetch_all();

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

    $mysql->close();

    echo "Keyboard_chinese_obj.showCandidates(" . $id . ",'$py'," . $t . "," . $t1 . "," . $u . "," . $u1 . ");";
  }
