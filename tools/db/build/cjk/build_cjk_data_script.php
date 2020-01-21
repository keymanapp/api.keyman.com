<?php
  // Import chinese_pinyin.txt and japanese.txt into database

  class build_cjk_data {
    private $script_path;
    private $force;

    function execute($data_root, $do_force) {
      $this->script_path = $data_root;
      $this->force = $do_force;

      if(($v = $this->create_tab_delimited_data_script('chinese_pinyin.txt', 'chinese_pinyin_import.sql', 'kmw_chinese_pinyin')) === FALSE) {
        fail("Failed to build chinese_pinyin sql");
      }

      if(($v = $this->create_tab_delimited_data_script('japanese.txt', 'japanese_import.sql', 'kmw_japanese')) === FALSE) {
        fail("Failed to build japanese sql");
      }

      return true;
    }

    function create_tab_delimited_data_script($tabfilename, $sqlfilename, $table) {
      $path = str_replace('\\', '/', $this->script_path);

      $sql = <<<END
        load data local infile '{$path}$tabfilename'
          into table $table
          character set utf8
          lines terminated by '\\r\\n'
          ignore 1 lines;
END;

      file_put_contents($this->script_path . $sqlfilename, $sql) || fail("Unable to write $sqlfilename to {$this->script_path}");
      return true;
    }
  }
?>