<?php
  require_once(__DIR__ . '/../../tools/util.php');

  allow_cors();
  json_response();

  require_once(__DIR__ . '/increment-download.inc.php');
  require_once(__DIR__ . '/../../tools/db/db.php');
  require_once __DIR__ . '/../../tools/autoload.php';

  use Keyman\Site\Common\KeymanHosts;

  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  header('Link: <' . KeymanHosts::Instance()->api_keyman_com .'/schemas/increment-download/1.0/increment-download.json#>; rel="describedby"');

  $AllowGet = isset($_REQUEST['debug']);

  if(!$AllowGet && $_SERVER['REQUEST_METHOD'] != 'POST') {
    fail('POST required');
  }

  if(!isset($_REQUEST['key'])) {
    fail('key parameter must be set');
  }

  // Note: we don't currently unit-test this one
  if(KeymanHosts::Instance()->Tier() === KeymanHosts::TIER_DEVELOPMENT)
    $key = 'local';
  else
    $key = $_ENV['API_KEYMAN_COM_INCREMENT_DOWNLOAD_KEY'];

  if($_REQUEST['key'] !== $key) {
    fail('Invalid key');
  }

  if(!isset($_REQUEST['id'])) {
    fail('id parameter must be set');
  }
  $id = $_REQUEST['id'];


  /**
   * POST https://api.keyman.com/increment-download/id
   *
   * Increments the download counter for a single keyboard info for the keyboard identified by `id`
   * Returns the new total count for the keyboard for the day
   *
   * https://api.keyman.com/schemas/increment-download.json is JSON schema
   *
   * @param id    the identifier of the keyboard to increment
   * @param key   internal key to allow endpoint to run
   */

  $json = \Keyman\Site\com\keyman\api\Keyboard::execute($mssql, $id);
  if($json === NULL) {
    fail('Keyboard not found', 404);
  }

  echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);