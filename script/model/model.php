<?php
  require_once(__DIR__ . '/../../tools/util.php');

  allow_cors();
  json_response();

  require_once(__DIR__ . '/model.inc.php');
  require_once(__DIR__ . '/../../tools/db/db.php');
  require_once __DIR__ . '/../../tools/autoload.php';
  use Keyman\Site\com\keyman\api\KeymanHosts;

  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  header('Link: <' . KeymanHosts::Instance()->api_keyman_com . '/schemas/model_info.distribution.json#>; rel="describedby"');

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