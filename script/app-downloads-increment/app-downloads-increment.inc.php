<?php
  namespace Keyman\Site\com\keyman\api;

  require_once(__DIR__ . '/../../tools/util.php');

  class AppDownloads {
    static function increment($mssql, $product, $version, $tier) {

      $stmt = $mssql->prepare('EXEC sp_app_downloads_increment :product, :version, :tier');
      $stmt->bindParam(":product", $product);
      $stmt->bindParam(":version", $version);
      $stmt->bindParam(":tier", $tier);
      $stmt->execute();
      $data = $stmt->fetchAll();
      if(count($data) == 0) {
        return NULL;
      }

      $obj = [
        'product' => $data[0]['product'],
        'version' => $data[0]['version'],
        'tier' => $data[0]['tier'],
        'count' => intval($data[0]['count'])
      ];

      return $obj;
    }
  }
