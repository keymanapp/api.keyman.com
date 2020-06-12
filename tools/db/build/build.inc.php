<?php
  /* Download files from downloads.keyman.com/db/data/ and downloads.keyman/com/db/scripts/ */
  require_once(dirname(__FILE__).'/../servervars.php');
  require_once('build_standards_data_script.inc.php');
  require_once('build_keyboards_script.inc.php');
  require_once('build_models_script.inc.php');

  function reportTime() {
    global $report_last_time;
    $new_time = microtime(true);
    build_log("Timestamp: ".sprintf("%0.02f", ($new_time-$report_last_time)));
    $report_last_time = $new_time;
  }

  // TODO: convert to class

  function BuildDatabase($DBDataSources, $mssqldb, $do_force) {
    build_log("Building database $mssqldb");

    global $report_last_time;
    $report_last_time = microtime(true);

    wakeUpDatabaseServer('master'); //$mssqldb);

    $data_path = dirname(dirname(dirname(dirname(__FILE__)))) . "/.data/";

    $builder = new build_sql_standards_data($DBDataSources);
    $builder->execute($data_path, $do_force) || fail("Unable to build standards data scripts");

    $builder = new build_keyboards_sql($DBDataSources);
    $builder->execute($data_path, $do_force) || fail("Unable to build keyboards data scripts");

    $builder = new build_models_sql($DBDataSources);
    $builder->execute($data_path, $do_force) || fail("Unable to build lexical models data scripts");

    buildDBDataSources($data_path, $DBDataSources);

    global $mssql_create_databases;
    if(isset($mssql_create_databases))
      sqlrun(dirname(__FILE__)."/create-database.sql", 'master', false);
    sqlrun(dirname(__FILE__)."/search.sql", $mssqldb);
    sqlrun(dirname(__FILE__)."/langtags.sql", $mssqldb);
    sqlrun(dirname(__FILE__)."/search-queries.sql", $mssqldb);
    sqlrun(dirname(__FILE__)."/model-queries.sql", $mssqldb);
    sqlrun(dirname(__FILE__)."/legacy-queries.sql", $mssqldb);
    sqlrun("${data_path}langtags.json.sql", $mssqldb);
    sqlrun("${data_path}language-subtag-registry.sql", $mssqldb);
    sqlrun("${data_path}iso639-3.sql", $mssqldb);
    sqlrun("${data_path}iso639-3-name-index.sql", $mssqldb);
    sqlrun("${data_path}ethnologue_language_codes.sql", $mssqldb);
    sqlrun("${data_path}ethnologue_country_codes.sql", $mssqldb);
    sqlrun("${data_path}ethnologue_language_index.sql", $mssqldb);
    sqlrun("${data_path}keyboards.sql", $mssqldb);
    sqlrun("${data_path}models.sql", $mssqldb);
    sqlrun(dirname(__FILE__)."/search-prepare-data.sql", $mssqldb);
    sqlrun(dirname(__FILE__)."/indexes.sql", $mssqldb);
    sqlrun("${data_path}dbdatasources.sql", $mssqldb);
    sqlrun(dirname(__FILE__)."/full-text-indexes.sql", $mssqldb, false);
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

  function sqlrun($sql, $db = 'master', $transaction = true) {
    reportTime();
    build_log("Running $sql");
    $s = file_get_contents($sql);
    $s = preg_split('/^\s*GO\s*$/m', $s);

    global $mssqlconninfo, $mysqluser, $mysqlpw;
    try {
      $mssql = new PDO($mssqlconninfo . $db, $mysqluser, $mysqlpw, [ "CharacterSet" => "UTF-8" ]);
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


