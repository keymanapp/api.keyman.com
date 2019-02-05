<?php
  require_once('../../tools/util.php');
  require_once('../../tools/keymanversion.php');

  define("LEGACY_WEB_STABLE_VERSION", "473"); // Corresponds to the last legacy stable web version 2.0.473
  allow_cors();

  /**
   * https://api.keyman.com/version?platform=p-string&level=l-string
   * https://api.keyman.com/version/platform/level
   *
   * Returns the latest version object for Keyman product 'platform' and release tier 'level'.
   *
   * If no parameters given, a legacy version string "473" that corresponds to the
   * last legacy stable web version 2.0.473 is returned.
   *
   * https://api.keyman.com/schemas/version.json is JSON schema
   *
   * @param platform    p-string  string of Keyman platform: android, ios, mac, windows, web.
   * If not provided, the platform 'web' will be used.
   * @param level       l-string  string of release tier: stable, beta, alpha.
   * If not provided, the level 'stable' will be used.
   */

  $keymanVersion = new keymanversion();

  if (isset($_REQUEST['command']) && $_REQUEST['command'] == 'cache') {
    // We'll refresh the backend cache, without testing the JSON data first
    $keymanVersion->recache();
  }

  if (empty($_REQUEST['level']) && empty($_REQUEST['platform'])) {
    // respond with legacy stable web version
    text_response();
    echo LEGACY_WEB_STABLE_VERSION;
    exit;
  }

  // Proceed with json response
  header('Link: <https://api.keyman.com/schemas/version.json#>; rel="describedby"');

  /*
    Test for stability parameter. If not provided, assume 'stable'
  */
  if (!empty($_REQUEST['level'])) {
    $level = $_REQUEST['level'];
    if (!preg_match('/^(stable|beta|alpha)$/', $level)) {
      fail('Invalid level parameter - stable, beta, or alpha expected');
    }
  } else {
    $level = 'stable';
  }

  if (!empty($_REQUEST['platform'])) {
    $platform = $_REQUEST['platform'];
    if (!preg_match('/^(android|ios|mac|windows|web)$/', $platform)) {
      fail('Invalid platform parameter - android, ios, mac, windows or web expected');
    }
  } else {
    $platform = 'web';
  }

  $ver = $keymanVersion->getVersion($platform, $level);

  if (!empty($ver)) {
    $verdata = array('platform' => $platform, 'level' => $level, 'version' => $ver);
  } else {
    $verdata = array('platform' => $platform, 'level' => $level, 'error' => 'No version exists for given platform and level');
  }
  json_response();
  echo json_encode($verdata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>