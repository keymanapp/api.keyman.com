<?php
  /* Download files from downloads.keyman.com/db/data/ and downloads.keyman/com/db/scripts/ */
  require_once(dirname(__FILE__).'/../servervars.php');
  require_once('build_standards_data_script.inc.php');
  require_once('build_keyboards_script.inc.php');
  require_once('build_models_script.inc.php');
  require_once(__DIR__ . '/build_analytics.inc.php');

  function reportTime() {
    global $report_last_time;
    $new_time = microtime(true);
    build_log("Timestamp: ".sprintf("%0.02f", ($new_time-$report_last_time)));
    $report_last_time = $new_time;
  }

  class BuildDatabaseClass {

    protected $mssqldb, $schema;

    function reportTime() {
      // TODO: unify
      global $report_last_time;
      $new_time = microtime(true);
      build_log("Timestamp: ".sprintf("%0.02f", ($new_time-$report_last_time)));
      $report_last_time = $new_time;
    }

    function BuildDatabase($DBDataSources, $mssqldb, $schema, $do_force) {
      build_log("Building database $mssqldb.$schema");

      $this->mssqldb = $mssqldb;
      $this->schema = $schema;

      global $report_last_time;
      $report_last_time = microtime(true);

      $this->wakeUpDatabaseServer('master'); //$mssqldb);

      $data_path = dirname(dirname(dirname(dirname(__FILE__)))) . "/.data/";

      $builder = new build_sql_standards_data($DBDataSources, $schema);
      $builder->execute($data_path, $do_force) || fail("Unable to build standards data scripts");

      $builder = new build_keyboards_sql($DBDataSources, $schema);
      $builder->execute($data_path, $do_force) || fail("Unable to build keyboards data scripts");

      $builder = new build_models_sql($DBDataSources, $schema);
      $builder->execute($data_path, $do_force) || fail("Unable to build lexical models data scripts");

      $builder = new build_analytics_sql($DBDataSources, $schema);
      $builder->execute($data_path) || fail("Unable to build analytics data scripts");

      $this->buildDBDataSources($data_path, $DBDataSources);

      global $mssql_create_database;
      if(isset($mssql_create_database)) {
        //
        $this->createSqlLogin() || fail("Unable to create logins");
        $this->sqlrun(dirname(__FILE__)."/create-database.sql", true, false);
      }

      $this->sqlrun(dirname(__FILE__)."/clean-database.sql", false, false);

      $this->sqlrun(dirname(__FILE__)."/search.sql");
      $this->sqlrun(dirname(__FILE__)."/langtags.sql");
      $this->sqlrun("${data_path}langtags.json.sql");
      $this->sqlrun("${data_path}language-subtag-registry.sql");
      $this->sqlrun("${data_path}iso639-3.sql");
      $this->sqlrun("${data_path}iso639-3-name-index.sql");
      $this->sqlrun("${data_path}ethnologue_language_codes.sql");
      $this->sqlrun("${data_path}ethnologue_country_codes.sql");
      $this->sqlrun("${data_path}ethnologue_language_index.sql");
      $this->sqlrun("${data_path}keyboards.sql");
      $this->sqlrun("${data_path}models.sql");

      if(file_exists("${data_path}analytics.sql"))
        $this->sqlrun("${data_path}analytics.sql");

      $this->sqlrun(dirname(__FILE__)."/search-prepare-data.sql");
      $this->sqlrun(dirname(__FILE__)."/indexes.sql");

      $this->sqlrun(dirname(__FILE__)."/full-text-indexes.sql", false, false);
      $this->sqlrun(dirname(__FILE__)."/search-queries.sql");

      // All scripts with sp_ prefixes will be automatically run
      // TODO: progressively move all stored procedures to this structure
      $scripts = glob(__DIR__ . '/sp_*.sql');
      foreach($scripts as $script) {
        $this->sqlrun($script);
      }

      $this->sqlrun(dirname(__FILE__)."/model-queries.sql");
      $this->sqlrun(dirname(__FILE__)."/legacy-queries.sql");

      $this->sqlrun("${data_path}dbdatasources.sql");
      return true;
    }

    function buildDBDataSources($data_path, DBDataSources $DBDataSources) {
      $sql = '';

      foreach($DBDataSources as $field => $value) {
        $sql .= "\nINSERT t_dbdatasources SELECT ".sqlv($DBDataSources, $field).", ".sqlv(null, basename($DBDataSources->$field)). ", " . $DBDataSources->downloadDate($value) ."\n";
      }

      file_put_contents("${data_path}dbdatasources.sql", $sql);
    }

    function download($url) {
      $filename = basename($url);
      build_log("Downloading $filename");
      if(($data = file_get_contents($url)) === false) {
        fail("Unable to download $url");
      }
      file_put_contents($filename, $data);
      return true;
    }

    function sqlrun($sql, $useMaster = false, $transaction = true) {
      global $mssqldb;

      $this->reportTime();
      build_log("Running $sql");
      $s = file_get_contents($sql);
      $s = str_replace('$keyboards', $this->mssqldb, $s);
      $s = str_replace('$schema', $this->schema, $s);

      $s = preg_split('/^\s*GO\s*$/m', $s);

      global $mssqlconninfo, $mysqluser, $mysqlpw;
      try {
        $mssql = new PDO($mssqlconninfo . ($useMaster ? 'master' : $this->mssqldb),
          ($useMaster ? $mysqluser : $this->schema), $mysqlpw,
          [ "CharacterSet" => "UTF-8" ]);
        $mssql->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $mssql->setAttribute( PDO::SQLSRV_ATTR_DIRECT_QUERY, true);
        $mssql->setAttribute( PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8 );
      }
      catch( PDOException $e ) {
        die( "Error connecting to SQL Server: " . $e->getMessage() );
      }

      try {
        if($transaction) $mssql->beginTransaction();

        foreach($s as $cmd) {
          if(trim($cmd) == '') continue;
          $mssql->exec($cmd);
          //build_log("$res rows affected\n");
        }

        if($transaction) $mssql->commit();
      } catch(PDOException $e) {
        $ei = $mssql->errorInfo();
        print_r($ei);
        fail("Failure: {$e}\n\n");
      }
    }

    function createSqlLogin() {
      build_log("Creating database login $this->schema");
      $dci = new DatabaseConnectionInfo();
      $mssql = new PDO($dci->getMasterConnectionString(), $dci->getUser(), $dci->getPassword(), [ "CharacterSet" => "UTF-8" ]);
      $mssql->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

      $pw = $mssql->quote($dci->getPassword());
      // note: cannot use parameterized query due to limitations with how parameters are
      // passed to DDL type statements
      $stmt = $mssql->prepare("
        IF NOT EXISTS
          (SELECT name FROM master.sys.server_principals WHERE name = '$this->schema')
        BEGIN
          CREATE LOGIN [$this->schema] WITH PASSWORD = $pw
        END");
      return $stmt->execute();
    }

    function wakeUpDatabaseServer($db) {
      global $mssqlconninfo, $mysqluser, $mysqlpw;
      $tries = 1;
      while(true) {
        build_log("Attempting to wake $db (attempt $tries/5)");
        try {
          $mssql = new PDO($mssqlconninfo . $db, $mysqluser, $mysqlpw, [ "CharacterSet" => "UTF-8" ]);
          return true;
        }
        catch( PDOException $e ) {
          $tries++;
          if($tries > 5) {
            die( "Unable to wake SQL Server $db after 5 attempts: " . $e->getMessage() );
          }
        }
      }
    }
  }

