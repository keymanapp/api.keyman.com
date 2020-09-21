<?php

namespace Keyman\Site\com\keyman\api;

require_once(__DIR__ . '/../../tools/util.php');
require_once(__DIR__ . '/../../tools/autoload.php');

use Keyman\Site\com\keyman\api\KeymanUrls;

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
                k.version, k.package_filename,
                k.platform_android, k.platform_linux, k.platform_macos, k.platform_ios, k.platform_web, k.platform_windows,
                kr.keyboard_id deprecated_by_keyboard_id
              FROM
                t_keyboard k LEFT JOIN
                t_keyboard_related kr ON k.keyboard_id = kr.related_keyboard_id AND kr.deprecates = 1
              WHERE
                k.keyboard_id = ?'
        );
        $stmt->bindParam(1, $keyboard);
        $stmt->execute();
        $data = $stmt->fetchAll();
        if (count($data) == 0) {
          $json["keyboards"][$keyboard] = ['error' => 'not found'];
        } else {

          $json["keyboards"][$keyboard] = [
            'version' => $data[0][0]
          ];

          if (!empty($platform) && !$data[0][array_search($platform, PackageVersion::available_platforms()) + 2]) {
            $json["keyboards"][$keyboard]['error'] = 'not available for platform';
          }

          if(!empty($data[0][1])) {
            $json["keyboards"][$keyboard]['kmp'] = KeymanUrls::keyboard_download_url($keyboard, $data[0][0], $data[0][1]);
          } else {
            $json["keyboards"][$keyboard]['error'] = 'not available as package';
          }

          if(!empty($data[0]['deprecated_by_keyboard_id'])) {
            $json["keyboards"][$keyboard]['deprecatedBy'] = $data[0]['deprecated_by_keyboard_id'];
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
            'kmp' => KeymanUrls::model_download_url($model, $data[0][0], $data[0][1])
          ];
        }
      }
    }
    return $json;
  }
}
