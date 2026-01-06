<?php
/*
 * Keyman is copyright (C) SIL Global. MIT License.
 *
 * Basic annual statistics for SIL reports
 */

  namespace Keyman\Site\com\keyman\api;

class AnnualStatistics {

  function execute($mssql, $startDate, $endDate) {
    return $this->_execute('sp_annual_statistics', $mssql, $startDate, $endDate);
  }

  function executeDownloadsByMonth($mssql, $startDate, $endDate) {
    return $this->_execute('sp_keyboard_downloads_by_month_statistics', $mssql, $startDate, $endDate);
  }

  function executeKeyboards($mssql, $startDate, $endDate) {
    return $this->_execute('sp_statistics_keyboard_downloads_by_id', $mssql, $startDate, $endDate);
  }

  private function _execute($proc, $mssql, $startDate, $endDate) {
    $stmt = $mssql->prepare("EXEC $proc :prmStartDate, :prmEndDate");

    $stmt->bindParam(":prmStartDate", $startDate);
    $stmt->bindParam(":prmEndDate", $endDate);

    $stmt->execute();
    $data = $stmt->fetchAll();
    return $this->filter_columns_by_name($data);
  }

  // strip out repeated columns with numeric keys (by default the results returned
  // give each column twice, once with a column name, and once with a column index)
  private function filter_columns_by_name($data) {
    $result = [];
    foreach($data as $row) {
      $r = [];
      foreach($row as $id => $val) {
        if(!is_numeric($id)) {
          $r[$id] = is_numeric($val) ? intval($val) : $val;
        }
      }
      array_push($result, $r);
    }
    return $result;
  }
}
