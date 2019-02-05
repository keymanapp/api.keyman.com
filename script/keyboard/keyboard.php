<?php
  require_once('../../tools/db/db.php');
  require_once('../../tools/util.php');
  
  allow_cors();
  json_response();
  
  header('Link: <https://api.keyman.com/schemas/keyboard_info.distribution.json#>; rel="describedby"');

  if(!isset($_REQUEST['id'])) {
    fail('id parameter must be set');
  }

  $id = $_REQUEST['id'];
  
  /**
    https://api.keyman.com/keyboard/id
    
    Returns a single keyboard info for the keyboard identified by `id`.
    
    https://api.keyman.com/schemas/keyboard_info.distribution.json is JSON schema
    
    @param id    the identifier of the keyboard to lookup
  */
  
  if(($stmt = $mysql->prepare('SELECT keyboard_info FROM t_keyboard WHERE keyboard_id = ?')) === false) {
    fail("Failed to prepare query: {$mysql->error}\n");
  }
  
  $stmt->bind_param("s", $id);
    
  if($stmt->execute()) {
    $result = $stmt->get_result();
    $data = $result->fetch_all();
    if(count($data) == 0) {
      fail('Keyboard not found');
    }
    $json = json_decode($data[0][0]);
    
    // Add the related keyboards that are deprecated
    
    if(($stmt = $mysql->prepare('SELECT keyboard_id FROM t_keyboard_related WHERE related_keyboard_id = ? AND deprecates <> 0')) === false) {
      fail("Failed to prepare query: {$mysql->error}\n");
    }
    
    $stmt->bind_param("s", $id);
    
    if($stmt->execute()) {
      $result = $stmt->get_result();
      $data = $result->fetch_all();
      for($i = 0; $i < count($data); $i++) {
        if(!isset($json->related)) {
          $json->related = new stdClass();
        }
        $name = $data[$i][0];
        $json->related->$name = array("deprecatedBy" => true);
      }
    }
    echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
  } else {
    fail("Failed to run: {$mysql->error}\n");
  }      
    
  $mysql->close();
?>