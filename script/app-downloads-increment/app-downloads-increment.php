<?php
  require_once(__DIR__ . '/../../tools/util.php');

  allow_cors();
  json_response();

  require_once(__DIR__ . '/app-downloads-increment.inc.php');
  require_once(__DIR__ . '/../../tools/db/db.php');
  require_once __DIR__ . '/../../tools/autoload.php';

  use Keyman\Site\Common\KeymanHosts;

  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();
  $env = getenv();

  header('Link: <' . KeymanHosts::Instance()->api_keyman_com .'/schemas/app-downloads-increment/1.0/app-downloads-increment.json#>; rel="describedby"');

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
    $key = $env['API_KEYMAN_COM_INCREMENT_DOWNLOAD_KEY'];

  if($_REQUEST['key'] !== $key) {
    fail('Invalid key');
  }

  if(!isset($_REQUEST['product']) ||
    !isset($_REQUEST['version']) ||
    !isset($_REQUEST['tier'])
  ) {
    // We don't constrain what the product / version / tier may be here, because
    // we may add other products in the future
    fail('product, version, tier parameters must be set');
  }

  $product = $_REQUEST['product'];
  $version = $_REQUEST['version'];
  $tier = $_REQUEST['tier'];

  /**
   * POST https://api.keyman.com/app-downloads-increment/product/version/tier
   *
   * Increments the download counter for a single product identified by
   * `product`, `version`, and `tier`. Returns the new total count for the
   * product/version/tier for the day
   *
   * https://api.keyman.com/schemas/app-downloads-increment.json is JSON schema
   *
   * @param product    the name of the product to increment ( "android", "ios",
   *                   "linux", "macos", "web", "windows", "developer"...)
   * @param version    the version number ("1.2.3")
   * @param tier       the tier of the product ("alpha", "beta", "stable")
   * @param key        internal key to allow endpoint to run
   */

  $json = \Keyman\Site\com\keyman\api\AppDownloads::increment($mssql, $product, $version, $tier);
  if($json === NULL) {
    fail("Failed to increment stat, invalid parameters [$product, $version, $tier]?", 401);
  }

  echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);