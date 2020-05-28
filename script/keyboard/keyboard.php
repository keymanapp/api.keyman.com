<?php
  require_once('../../tools/util.php');

  allow_cors();
  json_response();

  require_once('../../tools/db/db.php');

  header('Link: <https://api.keyman.com/schemas/keyboard_info.distribution.json#>; rel="describedby"');

  if(!isset($_REQUEST['id'])) {
    fail('id parameter must be set');
  }

  $id = $_REQUEST['id'];

  /**
   * https://api.keyman.com/keyboard/id
   *
   * Returns a single keyboard info for the keyboard identified by `id`.
   *
   * https://api.keyman.com/schemas/keyboard_info.distribution.json is JSON schema
   *
   * @param id    the identifier of the keyboard to lookup
   */

  $stmt = $mssql->prepare('SELECT keyboard_info FROM t_keyboard WHERE keyboard_id = :keyboard_id');
  $stmt->bindParam(":keyboard_id", $id);
  $stmt->execute();
  $data = $stmt->fetchAll();
  if(count($data) == 0) {
    fail('Keyboard not found');
  }
  $json = json_decode($data[0][0]);

  // Add the related keyboards that are deprecated

  $stmt = $mssql->prepare('SELECT keyboard_id FROM t_keyboard_related WHERE related_keyboard_id = :related_keyboard_id AND deprecates <> 0');
  $stmt->bindParam(":related_keyboard_id", $id);
  $stmt->execute();
  $data = $stmt->fetchAll();
  for($i = 0; $i < count($data); $i++) {
    if(!isset($json->related)) {
      $json->related = new stdClass();
    }
    $name = $data[$i][0];
    $json->related->$name = array("deprecatedBy" => true);
  }

  echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
?>