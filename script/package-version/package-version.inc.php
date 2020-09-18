<?php

namespace Keyman\Site\com\keyman\api;

require_once(__DIR__ . '/../../tools/util.php');
require_once(__DIR__ . '/../../tools/autoload.php');

use Keyman\Site\Common\KeymanHosts;

class PackageVersion
{
  static function available_platforms()
  {
    return ['android', 'ios', 'linux', 'mac', 'web', 'windows'];
  }

  function execute($mssql, $params, $platform)
  {
    // TODO: params should be expanded to keyboards, models

    // Prepare results

    $json = [];

    if (isset($params['keyboard'])) {
      $json['keyboards'] = [];

      foreach ($params['keyboard'] as $keyboard) {
        $stmt = $mssql->prepare(
          'SELECT
                version, package_filename,
                platform_android, platform_linux, platform_macos, platform_ios, platform_web, platform_windows
              FROM
                t_keyboard
              WHERE
                keyboard_id = ?'
        );
        $stmt->bindParam(1, $keyboard);
        $stmt->execute();
        $data = $stmt->fetchAll();
        if (count($data) == 0) {
          $json["keyboards"][$keyboard] = ['error' => 'not found'];
        } else {
          if (!empty($platform) && !$data[0][array_search($platform, PackageVersion::available_platforms()) + 2]) {
            $json["keyboards"][$keyboard] = ['error' => 'not found'];
          } else {
            $json["keyboards"][$keyboard] = [
              'version' => $data[0][0],
              'kmp' => $this->keyboard_download_url($keyboard, $data[0][0], $data[0][1])
            ];
          }
        }
      }
    }

    if (isset($params['model'])) {
      $json['models'] = [];

      foreach ($params['model'] as $model) {
        $stmt = $mssql->prepare('SELECT version, package_filename FROM t_model WHERE model_id = ?');
        $stmt->bindParam(1, $model);
        $stmt->execute();
        $data = $stmt->fetchAll();
        if (count($data) == 0) {
          $json["models"][$model] = ['error' => 'not found'];
        } else {
          // Note: we don't currently test platform for models
          $json["models"][$model] = [
            'version' => $data[0][0],
            'kmp' => $this->model_download_url($model, $data[0][0], $data[0][1])
          ];
        }
      }
    }
    return $json;
  }

  //
  // Hard-coded path to keyboards so we don't have to query
  // downloads.keyman.com/api/keyboard for each keyboard
  //
  function keyboard_download_url($id, $version, $package)
  {
    return KeymanHosts::Instance()->keyman_com . "/go/package/download/keyboard/" .
      rawurlencode($id) .
      "?" .
      (empty($version) ? "" : "version=" . rawurlencode($version) . "&") .
      "update=1";
  }

  //
  // Hard-coded path to models so we don't have to query
  // downloads.keyman.com/api/keyboard for each keyboard
  //
  function model_download_url($id, $version, $package)
  {
    return KeymanHosts::Instance()->keyman_com . "/go/package/download/model/" .
      rawurlencode($id) .
      "?" .
      (empty($version) ? "" : "version=" . rawurlencode($version) . "&") .
      "update=1";
  }
}
