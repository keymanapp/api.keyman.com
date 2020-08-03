<?php

namespace {
  require_once(__DIR__ . '/../tools/db/servervars.php');
  require_once(__DIR__ . '/../tools/db/db.php');
  require_once(__DIR__ . '/../tools/db/build/datasources.inc.php');
  require_once(__DIR__ . '/../tools/db/build/build.inc.php');
  require_once(__DIR__ . '/../tools/db/build/cjk/build.inc.php');

  function build_log($message)
  {
    global $log;
    echo $message . "\n";
    $log .= $message . "\n";
  }
}

namespace Keyman\Site\com\keyman\api\tests {

use BuildCJKTableClass;

final class TestDBDataSources extends \DBDataSources
  {
    function __construct()
    {
      foreach($this as $field => $value) {
        $this->$field = $this->fileFromTestDataDir($this->$field);
      }
      $this->mockAnalyticsSqlFile = $this->fileFromTestDataDir("analytics.sql");
    }

    private function fileFromTestDataDir($uri)
    {
      return __DIR__ . '/data/' . basename($uri);
    }

    public function downloadDate($uri) {
      return filemtime($uri);
    }
  }

  class TestDBBuild
  {
    static function Build()
    {
      // Connect to database. TODO: refactor with DBConnect
      global $mssqldb;
      $dci = new \DatabaseConnectionInfo();

      $force = !empty($_SERVER['TEST_REBUILD']);
      unset($_SERVER['TEST_REBUILD']); // wow, but means this only gets run once :)

      $schema = $dci->getActiveSchema();
      try {
        $mssql = new \PDO($dci->getMasterConnectionString(), $dci->getUser(), $dci->getPassword(), [ "CharacterSet" => "UTF-8" ]);
        $mssql->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      } catch (\PDOException $e) {
        \build_log("Could not connect to server: {$e->getMessage()}\n");
        throw $e;
      }

      // First, test the existing database to see its data sources
      $DBDataSources = new TestDBDataSources();

      try {
        $mssql->exec("USE $mssqldb");
        $q = $mssql->query("
          IF OBJECT_ID('$schema.t_dbdatasources') IS NULL
            SELECT '' uri, 0 date ELSE SELECT uri, date FROM $schema.t_dbdatasources WHERE filename = 'langtags.json'
        ");
        $data = $q->fetchAll();
        $date = filemtime(__DIR__ . '/data/langtags.json');
        if (!$force && sizeof($data) == 1 && $data[0]['uri'] === $DBDataSources->uriLangTags && $data[0]['date'] == $date) return self::GetSchemaLogin();
      } catch(\Exception $e) {
        // Let's assume that the database is not in an expected state, and try and rebuild
        \build_log("Error checking state of database $mssqldb: {$e->getMessage()}. Attempting to rebuild for test.");
      }

      \build_log("Database $mssqldb.$schema is not currently in a valid state for testing. Rebuilding.\n");
      // Database sources are not from our test resources, so rebuild them

      $B = new BuildCJKTableClass();
      $B->BuildDatabase($DBDataSources, $mssqldb, $schema, true);
      $B->BuildCJKTables($DBDataSources, $mssqldb, $schema, true);

      return self::GetSchemaLogin();
    }

    static function GetSchemaLogin() {
      $dci = new \DatabaseConnectionInfo();
      $schema = $dci->getActiveSchema();
      try {
        $mssql = new \PDO($dci->getConnectionString(), $schema, $dci->getPassword(), [ "CharacterSet" => "UTF-8" ]);
        $mssql->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $mssql;
      } catch (\PDOException $e) {
        \build_log("Could not connect to server: {$e->getMessage()}\n");
        throw $e;
      }
    }
  }
}
