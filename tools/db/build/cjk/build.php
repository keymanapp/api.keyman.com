<?php
  require_once(dirname(__FILE__).'/../../servervars.php');
  require_once('build_cjk_data_script.php');

  function BuildDatabase($do_force) {
    $data_path = dirname(__FILE__).'/';

    sqlrun("${data_path}cjk_database.sql");

    $builder = new build_cjk_data();
    $builder->execute($data_path, $do_force) || fail("Unable to build cjk data");

    sqlrun("${data_path}chinese_pinyin_import.sql", 'cjk');
    sqlrun("${data_path}japanese_import.sql", 'cjk');

    return true;
  }

  function sqlrun($sql, $db = '') {
    build_log("Running $sql");
    global $mysqlhost, $mysqlpw, $mysqluser;
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