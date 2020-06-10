<?php
  require_once('../../tools/db/db.php');
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  function new_query($s) {
    global $mssql;
    return $mssql->prepare($s);
  }

  function DB_LoadKeyboards($id) {
    global $version1, $version2;
    $stmt = new_query('EXEC sp_legacy10_keyboard ?, ?, ?');
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $version1, PDO::PARAM_INT);
    $stmt->bindParam(3, $version2, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  function DB_LoadKeyboardLanguages($id) {
    $stmt = new_query('EXEC sp_legacy10_keyboard_languages ?');
    $stmt->bindParam(1, $id);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  function DB_LoadKeyboardsForLanguage($id) {
    global $version1, $version2;
    $stmt = new_query('EXEC sp_legacy10_keyboards_for_language ?, ?, ?');
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $version1, PDO::PARAM_INT);
    $stmt->bindParam(3, $version2, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  function DB_LoadAllKeyboardLanguagesByLanguage($id) {
    $stmt = new_query('EXEC sp_legacy10_languages_for_keyboards_for_language ?');
    $stmt->bindParam(1, $id);
    $stmt->execute();
    return DB_ExplodeByKeyboardID($stmt->fetchAll());
  }

  function DB_LoadAllKeyboardLanguages($id) {
    if(empty($id)) $id = null;
    $stmt = new_query('EXEC sp_legacy10_all_keyboard_languages ?');
    $stmt->bindParam(1, $id);
    $stmt->execute();
    return DB_ExplodeByKeyboardID($stmt->fetchAll());
  }

  function DB_ExplodeByKeyboardID($data) {
    $result = [];
    $keyboard_id = null;
    $languages = [];
    foreach($data as $row) {
      if($row['keyboard_id'] != $keyboard_id) {
        if(!empty($keyboard_id)) $result[$keyboard_id] = $languages;
        $languages = [];
        $keyboard_id = $row['keyboard_id'];
      }
      array_push($languages, $row);
    }
    if(isset($languages)) $result[$keyboard_id] = $languages;
    return $result;
  }

  function DB_LoadLanguages($id) {
    if(empty($id)) $id = null;
    global $version1, $version2;
    $stmt = new_query('CALL sp_legacy10_language(?, ?, ?)');
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $version1, PDO::PARAM_INT);
    $stmt->bindParam(3, $version2, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  /* Optimised version of DB_LoadLanguages -- splits keyboard and language queries so we have less data */

  function DB_LoadLanguages_0($id) {
    if(empty($id)) $id = null;
    global $version1, $version2;
    $stmt = new_query('CALL sp_legacy10_language_0(?, ?, ?)');
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $version1, PDO::PARAM_INT);
    $stmt->bindParam(3, $version2, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  function DB_LoadLanguages_0_Keyboards($id) {
    if(empty($id)) $id = null;
    global $version1, $version2;
    $stmt = new_query('CALL sp_legacy10_language_0_keyboards(?, ?, ?)');
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $version1, PDO::PARAM_INT);
    $stmt->bindParam(3, $version2, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

?>