<?php
/*
 * Keyman is copyright (C) SIL Global. MIT License.
 *
 * Basic annual statistics for SIL reports
 */

  namespace Keyman\Site\com\keyman\api;

  // strip out repeated columns with numeric keys (by default the results returned
  // give each column twice, once with a column name, and once with a column index)
  function filter_columns_by_name($data) {
    $result = [];
    foreach($data as $row) {
      $r = [];
      foreach($row as $id => $val) {
        if(!is_numeric($id)) {
          $r[$id] = intval($val);
        }
      }
      array_push($result, $r);
    }
    return $result;
  }

class AnnualStatistics {

  function execute($mssql, $startDate, $endDate) {

    $stmt = $mssql->prepare('EXEC sp_annual_statistics :prmStartDate, :prmEndDate');

    $stmt->bindParam(":prmStartDate", $startDate);
    $stmt->bindParam(":prmEndDate", $endDate);

    $stmt->execute();
    $data = $stmt->fetchAll();
    return filter_columns_by_name($data);
  }

  function executeDownloadsByMonth($mssql, $startDate, $endDate) {
    $stmt = $mssql->prepare('EXEC sp_keyboard_downloads_by_month_statistics :prmStartDate, :prmEndDate');

    $stmt->bindParam(":prmStartDate", $startDate);
    $stmt->bindParam(":prmEndDate", $endDate);

    $stmt->execute();
    $data = $stmt->fetchAll();
    return filter_columns_by_name($data);
  }
}
