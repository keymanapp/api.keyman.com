<?php
  require_once('../../tools/db/db.php');

  function new_query($s) {
    global $mysql;
    $stmt = $mysql->stmt_init();
    if(!$stmt) fail('Could not initialise statement');
    $stmt->prepare($s) || fail('Could not prepare statement');
    return $stmt;
  }

  function DB_LoadKeyboards($id) {
    global $version1, $version2;
    $stmt = new_query('CALL sp_legacy10_keyboard(?, ?, ?)');
    $stmt->bind_param('sii', $id, $version1, $version2) || fail('Could not bind parameters for sp_legacy10_keyboard');
    $stmt->execute() || fail('Unable to execute sp_legacy10_keyboard load');
    if(($result = $stmt->get_result()) === FALSE) {
      fail('Unable to get query results');
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    return $data;
  }

  function DB_LoadKeyboardLanguages($id) {
    $stmt = new_query('CALL sp_legacy10_keyboard_languages(?)');
    $stmt->bind_param('s', $id) || fail('Could not bind parameters for sp_legacy10_keyboard_languages');
    $stmt->execute() || fail('Unable to execute sp_legacy10_keyboard_languages load');
    if(($result = $stmt->get_result()) === FALSE) {
      fail('Unable to get query results');
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    return $data;
  }

  function DB_LoadKeyboardsForLanguage($id) {
    global $version1, $version2;
    $stmt = new_query('CALL sp_legacy10_keyboards_for_language(?, ?, ?)');
    $stmt->bind_param('sii', $id, $version1, $version2) || fail('Could not bind parameters for sp_legacy10_keyboards_for_language');
    $stmt->execute() || fail('Unable to execute sp_legacy10_keyboards_for_language load');
    if(($result = $stmt->get_result()) === FALSE) {
      fail('Unable to get query results');
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    return $data;
  }

  function DB_LoadAllKeyboardLanguagesByLanguage($id) {
    $stmt = new_query('CALL sp_legacy10_languages_for_keyboards_for_language(?)');
    $stmt->bind_param('s', $id) || fail('Could not bind parameters for sp_legacy10_languages_for_keyboards_for_language');
    $stmt->execute() || fail('Unable to execute sp_legacy10_languages_for_keyboards_for_language load');
    if(($result = $stmt->get_result()) === FALSE) {
      fail('Unable to get query results');
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    return DB_ExplodeByKeyboardID($data);
  }

  function DB_LoadAllKeyboardLanguages($id) {
    if(empty($id)) $id = null;
    $stmt = new_query('CALL sp_legacy10_all_keyboard_languages(?)');
    $stmt->bind_param('s', $id) || fail('Could not bind parameters for sp_legacy10_all_keyboard_languages');
    $stmt->execute() || fail('Unable to execute sp_legacy10_all_keyboard_languages load');
    if(($result = $stmt->get_result()) === FALSE) {
      fail('Unable to get query results');
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    return DB_ExplodeByKeyboardID($data);
  }

  function DB_ExplodeByKeyboardID($data) {
    $result = [];
    $keyboard_id = null;
    foreach($data as $row) {
      if($row['keyboard_id'] != $keyboard_id) {
        if(isset($languages)) $result[$keyboard_id] = $languages;
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
    $stmt->bind_param('sii', $id, $version1, $version2) || fail('Could not bind parameters for sp_legacy10_language');
    $stmt->execute() || fail('Unable to execute sp_legacy10_language load');
    if(($result = $stmt->get_result()) === FALSE) {
      fail('Unable to get query results');
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    return $data;
  }

  /* Optimised version of DB_LoadLanguages -- splits keyboard and language queries so we have less data */

  function DB_LoadLanguages_0($id) {
    if(empty($id)) $id = null;
    global $version1, $version2;
    $stmt = new_query('CALL sp_legacy10_language_0(?, ?, ?)');
    $stmt->bind_param('sii', $id, $version1, $version2) || fail('Could not bind parameters for sp_legacy10_language_0');
    $stmt->execute() || fail('Unable to execute sp_legacy10_language_0 load');
    if(($result = $stmt->get_result()) === FALSE) {
      fail('Unable to get query results');
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    return $data;
  }

  function DB_LoadLanguages_0_Keyboards($id) {
    if(empty($id)) $id = null;
    global $version1, $version2;
    $stmt = new_query('CALL sp_legacy10_language_0_keyboards(?, ?, ?)');
    $stmt->bind_param('sii', $id, $version1, $version2) || fail('Could not bind parameters for sp_legacy10_language_0_keyboards');
    $stmt->execute() || fail('Unable to execute sp_legacy10_language_0_keyboards load');
    if(($result = $stmt->get_result()) === FALSE) {
      fail('Unable to get query results');
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    return $data;
  }

?>