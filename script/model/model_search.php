<?php
  require_once('../../tools/db/db.php');
  require_once('../../tools/util.php');

  allow_cors();
  json_response();

  // TODO: We probably need to describe this with a schema
  //header('Link: <https://api.keyman.com/schemas/model_info.distribution.json#>; rel="describedby"');

  if(!isset($_REQUEST['q'])) {
    fail('q parameter must be set');
  }

  $q = $_REQUEST['q'];

  /**
   * https://api.keyman.com/model?q=search
   *
   * Returns search results for the models matching `search`.
   *
   * @param search    the search string
   */

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
  $stmt->bindParam(":prmMatchType", $m, PDO::PARAM_INT);

  $stmt->execute();
  $data = $stmt->fetchAll();

  foreach($data as &$model) {
    $model = json_decode($model['model_info']);
    $model->packageFilename = get_model_download_url($model->id, $model->version, $model->packageFilename);
    $model->jsFilename = get_model_download_url($model->id, $model->version, $model->jsFilename);
    }
  json_print($data);
?>