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

  require_once('../../tools/db/db.php');
  require_once('../../tools/util.php');

  allow_cors();
  json_response();

  header('Link: <https://api.keyman.com/schemas/package-version.json#>; rel="describedby"');

  $params = fix_array_params($_SERVER['QUERY_STRING']);

  // Validate parameters

  $available_platforms = ['android','ios','linux','mac','web','windows'];

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

  // Prepare results

  $json = [];

  if(isset($params['keyboard'])) {
    $json['keyboards'] = [];

    foreach($params['keyboard'] as $keyboard) {
      if(($stmt = $mysql->prepare(
          'SELECT
            version, package_filename,
            platform_android, platform_linux, platform_macos, platform_ios, platform_web, platform_windows
          FROM
            t_keyboard
          WHERE
            keyboard_id = ?')) === false) {
        fail("Failed to prepare query: {$mysql->error}", 500);
      }

      $stmt->bind_param("s", $keyboard);

      if($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all();
        if(count($data) == 0) {
          $json["keyboards"][$keyboard] = ['error' => 'not found'];
        } else {
          if(isset($platform) && !$data[0][array_search($platform, $available_platforms)+2]) {
            $json["keyboards"][$keyboard] = ['error' => 'not found'];
          } else {
            $json["keyboards"][$keyboard] = [
              'version' => $data[0][0],
              'kmp' => keyboard_download_url($keyboard, $data[0][0], $data[0][1])
            ];
          }
        }
      } else {
        fail("Failed to execute query: {$mysql->error}", 500);
      }
    }
  }

  if(isset($params['model'])) {
    $json['models'] = [];

    foreach($params['model'] as $model) {
      if(($stmt = $mysql->prepare('SELECT version, package_filename FROM t_model WHERE model_id = ?')) === false) {
        fail("Failed to prepare query: {$mysql->error}", 500);
      }

      $stmt->bind_param("s", $model);

      if($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_all();
        if(count($data) == 0) {
          $json["models"][$model] = ['error' => 'not found'];
        } else {
          // Note: we don't currently test platform for models
          $json["models"][$model] = [
            'version' => $data[0][0],
            'kmp' => model_download_url($model, $data[0][0], $data[0][1])
          ];
        }
      } else {
        fail("Failed to execute query: {$mysql->error}", 500);
      }
    }
  }

  $mysql->close();

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
    $q = preg_replace('/\model=/', 'model[]=', $q);

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

  //
  // Hard-coded path to keyboards so we don't have to query
  // downloads.keyman.com/api/keyboard for each keyboard
  //
  function keyboard_download_url($id, $version, $package) {
    $id = urlencode($id);
    $version = urlencode($version);
    $package = urlencode($package);
    return "https://downloads.keyman.com/keyboards/$id/$version/$package";
  }

  //
  // Hard-coded path to models so we don't have to query
  // downloads.keyman.com/api/keyboard for each keyboard
  //
  function model_download_url($id, $version, $package) {
    $id = urlencode($id);
    $version = urlencode($version);
    $package = urlencode($package);
    return "https://downloads.keyman.com/models/$id/$version/$package";
  }
