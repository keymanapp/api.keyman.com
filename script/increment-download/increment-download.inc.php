<?php
  namespace Keyman\Site\com\keyman\api;

  require_once(__DIR__ . '/../../tools/util.php');

  class Keyboard {
    static function execute($mssql, $id) {

      $stmt = $mssql->prepare('EXEC sp_increment_download :keyboard_id');
      $stmt->bindParam(":keyboard_id", $id);
      $stmt->execute();
      $data = $stmt->fetchAll();
      if(count($data) == 0) {
        return NULL;
      }

      $obj = [
        'keyboard_id' => $data[0]['keyboard_id'],
        'count' => intval($data[0]['count'])
      ];

      return $obj;
    }
  }
