<?php
  require_once(__DIR__ . '/../../tools/util.php');

  allow_cors();
  json_response();

  require_once(__DIR__ . '/../../tools/db/db.php');
  require_once(__DIR__ . '/model-search.inc.php');
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  header('Link: <https://api.keyman.com/schemas/model-search.json#>; rel="describedby"');

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

  $ms = new \Keyman\Site\com\keyman\api\ModelSearch();
  $data = $ms->execute($mssql, $q);
  json_print($data);
