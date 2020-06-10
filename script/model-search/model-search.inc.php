<?php

namespace Keyman\Site\com\keyman\api;

require_once(__DIR__ . '/../../tools/util.php');

class ModelSearch {

  function execute($mssql, $q) {

    $stmt = $mssql->prepare('EXEC sp_model_search :prmSearchRegex, :prmSearchPlain, :prmMatchType');

    function RegexEscape($text) {
      $result = $text . '%';
      return $result;
    }

    if(substr($q, 0, 3) == 'id:') {
      $q = substr($q, 3);
      $m = 0;
    } else if(substr($q, 0, 6) == 'bcp47:') {
      // Search on this BCP47 code only
      $q = substr($q, 6);
      $m = 2;
    } else {
      $m = 1;
    }

    $qr = RegexEscape($q);
    $stmt->bindParam(":prmSearchRegex", $qr);
    $stmt->bindParam(":prmSearchPlain", $q);
    $stmt->bindParam(":prmMatchType", $m, \PDO::PARAM_INT);

    $stmt->execute();
    $data = $stmt->fetchAll();

    foreach($data as &$model) {
      $model = json_decode($model['model_info']);
      $model->packageFilename = get_model_download_url($model->id, $model->version, $model->packageFilename);
      $model->jsFilename = get_model_download_url($model->id, $model->version, $model->jsFilename);
      }

    return $data;
  }
}

