<?php
  require_once(__DIR__ . '/../../servervars.php');
  require_once(__DIR__ . '/build_cjk_data_script.inc.php');

  function BuildCJKTables($DBDataSources, $mssqldb, $do_force) {
    $data_path = __DIR__ . '/';

    sqlrun("${data_path}cjk_database.sql", $mssqldb);

    $builder = new build_cjk_data($DBDataSources);
    $builder->execute($data_path, $do_force) || fail("Unable to build cjk data");

    sqlrun("${data_path}chinese_pinyin_import.sql", $mssqldb);
    sqlrun("${data_path}japanese_import.sql", $mssqldb);

    return true;
  }

?>