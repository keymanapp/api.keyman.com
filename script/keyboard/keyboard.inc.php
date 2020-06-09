<?php
  namespace Keyman\Site\com\keyman\api;

  require_once(__DIR__ . '/../../tools/util.php');

  class Keyboard {
    static function execute($mssql, $id) {

      $stmt = $mssql->prepare('SELECT keyboard_info FROM t_keyboard WHERE keyboard_id = :keyboard_id');
      $stmt->bindParam(":keyboard_id", $id);
      $stmt->execute();
      $data = $stmt->fetchAll();
      if(count($data) == 0) {
        return NULL;
      }
      $json = json_decode($data[0][0]);

      // Add the related keyboards that are deprecated

      $stmt = $mssql->prepare('SELECT keyboard_id FROM t_keyboard_related WHERE related_keyboard_id = :related_keyboard_id AND deprecates <> 0');
      $stmt->bindParam(":related_keyboard_id", $id);
      $stmt->execute();
      $data = $stmt->fetchAll();
      for($i = 0; $i < count($data); $i++) {
        if(!isset($json->related)) {
          $json->related = new \stdClass();
        }
        $name = $data[$i][0];
        $json->related->$name = array("deprecatedBy" => true);
      }

      return $json;
    }
  }
