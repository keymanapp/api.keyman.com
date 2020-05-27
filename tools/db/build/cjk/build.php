<?php
  require_once(dirname(__FILE__).'/../../servervars.php');
  require_once('build_cjk_data_script.php');

  function BuildCJKTables($mssqldb, $do_force) {
    $data_path = dirname(__FILE__).'/';

    sqlrun("${data_path}cjk_database.sql", $mssqldb);

    $builder = new build_cjk_data();
    $builder->execute($data_path, $do_force) || fail("Unable to build cjk data");

    sqlrun("${data_path}chinese_pinyin_import.sql", $mssqldb);
    sqlrun("${data_path}japanese_import.sql", $mssqldb);

    return true;
  }

?>