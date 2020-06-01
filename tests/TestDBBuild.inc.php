<?php

namespace {
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

  final class TestDBDataSources extends \DBDataSources
  {
    function __construct()
    {
      foreach($this as $field => $value) {
        $this->$field = $this->fileFromTestDataDir($this->$field);
      }
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
      global $mssql, $activedb;

      // Always work on the first database in the pair
      $db = $activedb->get();

      // First, test the existing database to see its data sources
      $DBDataSources = new TestDBDataSources();

      try {
        $q = $mssql->query("IF OBJECT_ID('t_dbdatasources') IS NULL SELECT '' uri, 0 date ELSE SELECT uri, date FROM t_dbdatasources WHERE filename = 'langtags.json'");
        $data = $q->fetchAll();
        $date = filemtime(__DIR__ . '/data/langtags.json');
        if (sizeof($data) == 1 && $data[0]['uri'] === $DBDataSources->uriLangTags && $data[0]['date'] == $date) return;
      } catch(\Exception $e) {
        // Let's assume that the database is not in an expected state, and try and rebuild
        \build_log("Error checking state of database $db: {$e->getMessage()}. Attempting to rebuild for test.");
      }

      \build_log("Database $db is not currently in a valid state for testing. Rebuilding.\n");
      // Database sources are not from our test resources, so rebuild them
      BuildDatabase($DBDataSources, $db, true);
      BuildCJKTables($DBDataSources, $db, true);
    }
  }
}
