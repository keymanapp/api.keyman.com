<?php

namespace Keyman\Site\com\keyman\api\Tools\DB {
  require_once(__DIR__ . '/../util.php');
  require_once(__DIR__ . '/servervars.php');

  class DBConnect
  {
    static function Connect()
    {
      $activedb = new \ActiveDB();

      global $mssqlconninfo, $mysqluser, $mysqlpw;
      try {
        $mssql = new \PDO($mssqlconninfo . $activedb->get(), $mysqluser, $mysqlpw, NULL);
        $mssql->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      } catch (\PDOException $e) {
        fail("Error connecting to SQL Server", 500, "[$mssqlconninfo{$activedb->get()}]: " . $e->getMessage());
      }
      return $mssql;
    }
  }
}
