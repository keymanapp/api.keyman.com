<?php

namespace Keyman\Site\com\keyman\api;

require_once(__DIR__ . '/../../tools/util.php');
require_once(__DIR__ . '/../../tools/autoload.php');

use Keyman\Site\com\keyman\api\KeymanUrls;

class PackageVersion
{
  private $mssql;

  static function available_platforms() {
    return ['android', 'ios', 'linux', 'mac', 'web', 'windows'];
  }

  function __construct($mssql) {
    $this->mssql = $mssql;
  }

  function execute($keyboards, $models, $platform, $keymanVersion) {
    // Prepare results

    $json = [];

    if (count($keyboards) > 0) {
      $json['keyboards'] = [];

      foreach ($keyboards as $keyboard) {
        $json["keyboards"][$keyboard] = $this->getKeyboard($keyboard, $platform, $keymanVersion);
      }
    }

    if (count($models) > 0) {
      $json['models'] = [];

      foreach ($models as $model) {
        $json["models"][$model] = $this->getModel($model, $platform, $keymanVersion);
      }
    }

    return $json;
  }

  function getKeyboard($keyboard, $platform, $keymanVersion) {
    $stmt = $this->mssql->prepare(
      'SELECT
            k.version, k.package_filename,
            k.platform_android, k.platform_ios, k.platform_linux, k.platform_macos, k.platform_web, k.platform_windows,
            kr.keyboard_id deprecated_by_keyboard_id, k.min_keyman_version
          FROM
            t_keyboard k LEFT JOIN
            t_keyboard_related kr ON k.keyboard_id = kr.related_keyboard_id AND kr.deprecates = 1
          WHERE
            k.keyboard_id = ?'
    );
    $stmt->bindParam(1, $keyboard);
    $stmt->execute();
    $data = $stmt->fetchAll();

    $jsonKeyboard = [];

    if (count($data) == 0) {
      $jsonKeyboard['error'] = 'not found';
    } else {
      $dataKeyboard = $data[0];

      if (!empty($platform) && !$dataKeyboard[array_search($platform, PackageVersion::available_platforms()) + 2]) {
        $jsonKeyboard['error'] = "Not available for platform $platform";
      }
      else if(!empty($keymanVersion) && version_compare($keymanVersion, $dataKeyboard['min_keyman_version'], '<')) {
        $jsonKeyboard['error'] = "Keyman version {$dataKeyboard['min_keyman_version']}+ required";
      }
      else if(empty($dataKeyboard['package_filename'])) {
        $jsonKeyboard['error'] = 'not available as package';
      } else {
        $jsonKeyboard['version'] = $dataKeyboard['version'];
        $jsonKeyboard['kmp'] = KeymanUrls::keyboard_download_url($keyboard, $dataKeyboard['version'], $dataKeyboard['package_filename']);
        if(!empty($dataKeyboard['deprecated_by_keyboard_id'])) {
          $jsonKeyboard['deprecatedBy'] = $dataKeyboard['deprecated_by_keyboard_id'];
        }
      }
    }
    return $jsonKeyboard;
  }

  function getModel($model, $platform, $keymanVersion) {
    $stmt = $this->mssql->prepare('SELECT version, package_filename, min_keyman_version FROM t_model WHERE model_id = ?');
    $stmt->bindParam(1, $model);
    $stmt->execute();
    $data = $stmt->fetchAll();

    $jsonModel = [];

    if (count($data) == 0) {
      $jsonModel["error"] = 'not found';
    } else {
      $dataModel = $data[0];
      // Note: we don't currently test platform for models
      if(!empty($keymanVersion) && version_compare($keymanVersion, $dataModel['min_keyman_version'], '<')) {
        $jsonModel['error'] = "Keyman version {$dataModel['min_keyman_version']}+ required";
      } else {
        $jsonModel['version'] = $dataModel['version'];
        $jsonModel['kmp'] = KeymanUrls::model_download_url($model, $dataModel['version'], $dataModel['package_filename']);
      }
    }
    return $jsonModel;
  }
}
