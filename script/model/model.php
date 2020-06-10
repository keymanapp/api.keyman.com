<?php
  require_once(__DIR__ . '/../../tools/util.php');

  allow_cors();
  json_response();

  require_once(__DIR__ . '/model.inc.php');
  require_once(__DIR__ . '/../../tools/db/db.php');
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  header('Link: <https://api.keyman.com/schemas/model_info.distribution.json#>; rel="describedby"');

  if(!isset($_REQUEST['id'])) {
    fail('id parameter must be set');
  }

  $id = $_REQUEST['id'];

  /**
   * https://api.keyman.com/model/id
   *
   * Returns a single keyboard info for the model identified by `id`.
   *
   * https://api.keyman.com/schemas/model_info.distribution.json is JSON schema
   *
   * @param id    the identifier of the model to lookup
   */

  $json = \Keyman\Site\com\keyman\api\Model::getModelJson($mssql, $id);
  if($json === NULL) {
    fail('Model not found', 404);
  }

  echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
?>