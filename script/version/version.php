<?php
  require_once(__DIR__ . '/../../tools/util.php');
  require_once(__DIR__ . '/../../tools/keymanversion.php');
  require_once(__DIR__ . '/version.inc.php');
  require_once __DIR__ . '/../../tools/autoload.php';
  use Keyman\Site\Common\KeymanHosts;
  require_once __DIR__ . '/../../tools/db/servervars.php';

  define("LEGACY_WEB_STABLE_VERSION", "473"); // Corresponds to the last legacy stable web version 2.0.473
  allow_cors();

  /**
   * https://api.keyman.com/version?platform=$platform&level=$level
   * https://api.keyman.com/version/$platform/$level
   *
   * Returns the latest version object for Keyman product 'platform' and release
   * tier 'level'.
   *
   * If no parameters given, a legacy version string "473" that corresponds to
   * the last legacy stable web version 2.0.473 is returned.
   *
   * Note: we normally use the term 'tier' instead of 'level'. This API was
   *       written before we standardized on 'tier'.
   *
   * https://api.keyman.com/schemas/version.json is JSON schema
   *
   * @param platform    string of Keyman platform: 'android', 'ios', 'linux',
   *                    'mac', 'windows', 'web'. If not provided, the platform
   *                    'web' will be used.
   * @param level       string of release tier: 'stable', 'beta', 'alpha', or
   *                    'all'. If not provided, the tier 'stable' will be used.
   *
   * For 'stable', 'beta' and 'alpha' tiers, the data returned will be of the
   * format:
   *
   *   { "platform": "$platform", "level": "$level", "version": "$version" }
   *
   * For 'all' tier, the data returned will be of the format:
   *
   *   { "platform": "$platform", "alpha": "$alphaVersion",
   *     "beta": "$betaVersion", "stable": "$stableVersion" }
   */

  if (empty($_REQUEST['level']) && empty($_REQUEST['platform'])) {
    // respond with legacy stable web version
    text_response();
    echo LEGACY_WEB_STABLE_VERSION;
    exit;
  }

  json_response();

  // Proceed with json response
  header('Link: <' . KeymanHosts::Instance()->api_keyman_com . '/schemas/version.json#>; rel="describedby"');

  /*
    Test for stability parameter. If not provided, assume 'stable'
  */
  if (!empty($_REQUEST['level'])) {
    $level = $_REQUEST['level'];
    if (!preg_match('/^(stable|beta|alpha|all)$/', $level)) {
      fail('Invalid level parameter - stable, beta, alpha or all expected');
    }
  } else {
    $level = 'stable';
  }

  if (!empty($_REQUEST['platform'])) {
    $platform = $_REQUEST['platform'];
    if (!preg_match('/^(android|ios|linux|mac|windows|web)$/', $platform)) {
      fail('Invalid platform parameter - android, ios, linux, mac, windows or web expected');
    }
  } else {
    $platform = 'web';
  }

  $version = new \Keyman\Site\com\keyman\api\Version();

  if($level == 'all')
    $ver = $version->executeAll($platform);
  else
    $ver = $version->execute($platform, $level);
  echo json_encode($ver, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
