<?php
  require_once(__DIR__ . '/../../tools/util.php');

  allow_cors();
  json_response();

  require_once(__DIR__ . '/keyboard.inc.php');
  require_once(__DIR__ . '/../../tools/db/db.php');
  require_once __DIR__ . '/../../tools/autoload.php';

  use Keyman\Site\com\keyman\api\KeymanHosts;

  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  header('Link: <' . KeymanHosts::Instance()->api_keyman_com .'/schemas/keyboard_info.distribution.json#>; rel="describedby"');

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

  $json = \Keyman\Site\com\keyman\api\Keyboard::execute($mssql, $id);
  if($json === NULL) {
    fail('Keyboard not found', 404);
  }

  echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);