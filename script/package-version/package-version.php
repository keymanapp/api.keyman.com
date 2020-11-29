<?php
  /**
   *
   * https://api.keyman.com/package-version?keyboard=foo[,...][&keyboard=...]&model=bar[,...][&model=...]&platform=baz
   *
   * Endpoint for getting keyboard and lexical model versions. The results will
   * contain latest versions and links to the .kmp or .model.kmp packages.
   *
   * https://api.keyman.com/schemas/package-version.json is JSON schema for valid responses
   *
   * @param keyboard   Optional. keyboard id, can be repeated (either with comma or repeated param).
   * @param model      Optional. model id, can be repeated (either with comma or repeated param).
   * @param platform   Optional. Filter by platform support for keyboards:
   *                   android, ios, linux, mac, [web], windows (web does not currently support .kmp)
   *                   This stops the API returning keyboard packages that are invalid for the target
   *                   platform. If not supplied, does not filter by platform support.
   * @return           JSON blob or HTTP/400 (with JSON error) on invalid parameters
   *                   The valid blob will contain latest version and url for the keyboards/lexical models.
   */

  require_once('../../tools/util.php');
  require_once __DIR__ . '/../../tools/autoload.php';
  use Keyman\Site\Common\KeymanHosts;
  use Keyman\Site\com\keyman\api\PackageVersion;

  allow_cors();
  json_response();

  require_once(__DIR__ . '/package-version.inc.php');
  require_once('../../tools/db/db.php');
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  header('Link: <' . KeymanHosts::Instance()->api_keyman_com . '/schemas/package-version.json#>; rel="describedby"');

  $params = fix_array_params($_SERVER['QUERY_STRING']);

  // Validate parameters

  $available_platforms = PackageVersion::available_platforms();

  foreach($params as $param => $value) {
    if(!in_array($param, ['keyboard', 'model', 'platform'])) {
      fail("Invalid parameter $param");
    }
  }

  if(isset($params['platform'])) {
    $platform = $params['platform'];
    if(!in_array($platform, $available_platforms)) {
      fail("Invalid platform $platform");
    }
  }
  else $platform = null;
  // Prepare results

  $PackageVersion = new Keyman\Site\com\keyman\api\PackageVersion();
  $json = $PackageVersion->execute($mssql, $params, $platform);

  echo json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

  //
  // End of main
  //

  // Get both comma-delimited and repeated parameters for `keyboard` and `model`
  // PHP doesn't natively support repeated parameters without '[]' appended to their key
  // which is really ugly for 3rd party use, so reimplement the query string parsing
  // ourselves, and at the same time allow for either method of specifying the ids.
  function fix_array_params($q) {
    $res = [];
    $q = preg_replace('/\bkeyboard=/', 'keyboard[]=', $q);
    $q = preg_replace('/\bmodel=/', 'model[]=', $q);

    parse_str($q, $res);
    if(array_key_exists('keyboard', $res)) {
      $res['keyboard'] = explode_array_by_comma($res['keyboard']);
    }

    if(array_key_exists('model', $res)) {
      $res['model'] = explode_array_by_comma($res['model']);
    }

    return $res;
  }

  function explode_array_by_comma($array) {
    $items = [];
    foreach($array as $item) {
      $a = explode(',', $item);
      $items = array_merge($items, $a);
    }
    return $items;
  }
