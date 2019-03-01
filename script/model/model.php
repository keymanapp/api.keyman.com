<?php
  require_once('../../tools/db/db.php');
  require_once('../../tools/util.php');
  
  allow_cors();
  json_response();
  
  header('Link: <https://api.keyman.com/schemas/model_info.distribution.json#>; rel="describedby"');

  if(!isset($_REQUEST['id'])) {
    fail('id parameter must be set');
  }

  $id = $_REQUEST['id'];
  
  /**
    https://api.keyman.com/model/id
    
    Returns a single keyboard info for the model identified by `id`.
    
    https://api.keyman.com/schemas/model_info.distribution.json is JSON schema
    
    @param id    the identifier of the model to lookup
  */
  
  if(($stmt = $mysql->prepare('SELECT model_info FROM t_model WHERE model_id = ?')) === false) {
    fail("Failed to prepare query: {$mysql->error}\n");
  }
  
  $stmt->bind_param("s", $id);
    
  if($stmt->execute()) {
    $result = $stmt->get_result();
    $data = $result->fetch_all();
    if(count($data) == 0) {
      fail('Model not found', 404);
    }
    $json = json_decode($data[0][0]);
    $json->packageFilename = get_model_download_url($json->id, $json->version, $json->packageFilename);
    $json->jsFilename = get_model_download_url($json->id, $json->version, $json->jsFilename);
    
    // Add the related models that are deprecated
    
    if(($stmt = $mysql->prepare('SELECT model_id FROM t_model_related WHERE related_model_id = ? AND deprecates <> 0')) === false) {
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