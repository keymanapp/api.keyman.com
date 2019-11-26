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
    return $data;
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

    DB_LogStats("DB_LoadAllKeyboardLanguages($id)");
    return $data;
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
?>