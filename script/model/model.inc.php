<?php
  namespace Keyman\Site\com\keyman\api;

  require_once(__DIR__ . '/../../tools/util.php');

  class Model {
    static function getModelJson($mssql, $id) {
      $stmt = $mssql->prepare('SELECT model_info FROM t_model WHERE model_id = :model_id');
      $stmt->bindParam(":model_id", $id);

      $stmt->execute();
      $data = $stmt->fetchAll();
      if(count($data) == 0) {
        return null;
      }
      $json = json_decode($data[0][0]);
      $json->packageFilename = get_model_download_url($json->id, $json->version, $json->packageFilename);
      $json->jsFilename = get_model_download_url($json->id, $json->version, $json->jsFilename);

      // Add the related models that are deprecated

      $stmt = $mssql->prepare('SELECT model_id FROM t_model_related WHERE related_model_id = :related_model_id AND deprecates <> 0');
      $stmt->bindParam(":related_model_id", $id);
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
?>