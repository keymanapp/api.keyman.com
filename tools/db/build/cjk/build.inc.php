<?php
  require_once(__DIR__ . '/../../servervars.php');
  require_once(__DIR__ . '/build_cjk_data_script.inc.php');

  // TODO: this class inheritance is rather shambolic
  class BuildCJKTableClass extends BuildDatabaseClass {
    function BuildCJKTables($DBDataSources, $schema, $do_force) {
      $data_path = __DIR__ . '/';

      $this->schema = $schema;

      $this->sqlrun("${data_path}cjk_database.sql");

      $builder = new build_cjk_data($DBDataSources, $schema);
      $builder->execute($data_path, $do_force) || fail("Unable to build cjk data");

      $this->sqlrun("${data_path}chinese_pinyin_import.sql");
      $this->sqlrun("${data_path}japanese_import.sql");

      return true;
    }
  }
?>