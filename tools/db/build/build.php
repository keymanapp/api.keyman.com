<?php
  /* Download files from downloads.keyman.com/db/data/ and downloads.keyman/com/db/scripts/ */
  
  require_once(dirname(__FILE__).'/../servervars.php');
  require_once('build_standards_data_script.php');
  require_once('build_keyboards_script.php');

  function BuildDatabase($do_force) {
    global $mysqldb;
    
    $data_path = dirname(dirname(dirname(dirname(__FILE__)))) . "/.data/";

    $builder = new build_sql_standards_data();
    $builder->execute($data_path, false) || fail("Unable to build standards data scripts");

    $builder = new build_keyboards_sql();
    $builder->execute($data_path, $do_force) || fail("Unable to build keyboards data scripts");

    sqlrun(dirname(__FILE__)."/search.sql");
    sqlrun(dirname(__FILE__)."/search-queries.sql");
    sqlrun(dirname(__FILE__)."/legacy-queries.sql");
    sqlrun("${data_path}language-subtag-registry.sql", $mysqldb);
    sqlrun("${data_path}iso639-3.sql", $mysqldb);
    sqlrun("${data_path}iso639-3-name-index.sql", $mysqldb);
    sqlrun("${data_path}ethnologue_language_codes.sql", $mysqldb);
    sqlrun("${data_path}ethnologue_country_codes.sql", $mysqldb);
    sqlrun("${data_path}ethnologue_language_index.sql", $mysqldb);
    sqlrun("${data_path}keyboards.sql", $mysqldb);
    sqlrun(dirname(__FILE__)."/search-prepare-data.sql", $mysqldb);
    
    return true;
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
  
  function sqlrun($sql, $db = '') {
    build_log("Running $sql");
    global $mysqlhost, $mysqlpw, $mysqldb, $mysqluser;
    $s = file_get_contents($sql);
    $m = new mysqli($mysqlhost, $mysqluser, $mysqlpw, $db);
    if($m->multi_query($s)) {
      while($m->more_results()) {
        if(!$m->next_result()) {
          fail("Failure: {$m->error}");
        }
      }
    } else {
      fail("Failed to run $sql: $m->error");
    }      
    $m->close();
  }
  
?>