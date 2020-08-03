<?php

namespace Keyman\Site\com\keyman\api\Tools\DB {
  require_once(__DIR__ . '/../util.php');
  require_once(__DIR__ . '/servervars.php');

  class DBConnect
  {
    static function Connect()
    {
      $dci = new \DatabaseConnectionInfo();

      try {
        $mssql = new \PDO($dci->getConnectionString(), $dci->getActiveSchema(), $dci->getPassword(), [ "CharacterSet" => "UTF-8" ]);
        $mssql->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      } catch (\PDOException $e) {
        fail("Error connecting to SQL Server", 500, "[{$dci->getConnectionString()}]: " . $e->getMessage());
      }
      return $mssql;
    }
  }
}
