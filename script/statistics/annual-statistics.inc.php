<?php
/*
 * Keyman is copyright (C) SIL Global. MIT License.
 *
 * Basic annual statistics for SIL reports
 */

  namespace Keyman\Site\com\keyman\api;

  function filter_columns_by_name($key) {
    return !is_numeric($key);
  }

class AnnualStatistics {

  function execute($mssql, $startDate, $endDate) {

    $stmt = $mssql->prepare('EXEC sp_annual_statistics :prmStartDate, :prmEndDate');

    $stmt->bindParam(":prmStartDate", $startDate);
    $stmt->bindParam(":prmEndDate", $endDate);

    $stmt->execute();
    $data = $stmt->fetchAll()[0];
    $data = array_filter($data, "Keyman\\Site\\com\\keyman\\api\\filter_columns_by_name", ARRAY_FILTER_USE_KEY );
    return $data;
  }
}
