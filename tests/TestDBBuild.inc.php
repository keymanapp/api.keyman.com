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

namespace com\keyman\api\tests {

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
  }

  class TestDBBuild
  {
    static function Build()
    {
      global $mssqldb0, $mssql, $activedb;

      // Always work on the first database in the pair
      $activedb->set($mssqldb0);

      // First, test the existing database to see its data sources
      $DBDataSources = new TestDBDataSources();

      $q = $mssql->query("SELECT uri FROM t_dbdatasources WHERE filename = 'langtags.json'");
      $data = $q->fetchAll();
      if (sizeof($data) == 1 && $data[0]['uri'] === $DBDataSources->uriLangTags) return;

      // Database sources are not from our test resources, so rebuild them
      BuildDatabase($DBDataSources, $mssqldb0, true);
      BuildCJKTables($DBDataSources, $mssqldb0, true);
    }
  }
}
