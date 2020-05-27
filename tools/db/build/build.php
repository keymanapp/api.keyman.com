<?php
  /* Download files from downloads.keyman.com/db/data/ and downloads.keyman/com/db/scripts/ */

  require_once(dirname(__FILE__).'/../servervars.php');
  require_once('build_standards_data_script.php');
  require_once('build_keyboards_script.php');
  require_once('build_models_script.php');

  function reportTime() {
    global $report_last_time;
    $new_time = microtime(true);
    build_log("Timestamp: ".sprintf("%0.02f", ($new_time-$report_last_time)));
    $report_last_time = $new_time;
  }

  function BuildDatabase($mssqldb, $do_force) {
    build_log("Building database $mssqldb");

    global $report_last_time;
    $report_last_time = microtime(true);

    $data_path = dirname(dirname(dirname(dirname(__FILE__)))) . "/.data/";

    $builder = new build_sql_standards_data();
    $builder->execute($data_path, false) || fail("Unable to build standards data scripts");

    $builder = new build_keyboards_sql();
    $builder->execute($data_path, $do_force) || fail("Unable to build keyboards data scripts");

    $builder = new build_models_sql();
    $builder->execute($data_path, $do_force) || fail("Unable to build lexical models data scripts");

    global $mssqlconninfo_master;
    if(isset($mssqlconninfo_master))
      sqlrun(dirname(__FILE__)."/create-database.sql");
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
    sqlrun(dirname(__FILE__)."/full-text-indexes.sql", $mssqldb, false);
    return true;
  }

  function download($url) {
    $filename = basename($url);
    build_log("Downloading $filename");
    if(($data = file_get_contents($url)) === false) {
      fail("Unable to download $url: $php_errormsg");
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
      $mssql = new PDO($mssqlconninfo . $db, $mysqluser, $mysqlpw, NULL);
      $mssql->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
      $mssql->setAttribute( PDO::SQLSRV_ATTR_DIRECT_QUERY, true);
    }
    catch( PDOException $e ) {
      die( "Error connecting to SQL Server: " . $e );
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

