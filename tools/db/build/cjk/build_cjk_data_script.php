<?php
  require_once(__DIR__ . '/../common.php');
  // Import chinese_pinyin.txt and japanese.txt into database

  class build_cjk_data extends build_common {

    function execute($data_root, $do_force) {
      $this->script_path = $data_root;
      $this->force = $do_force;

      if(($v = $this->create_tab_delimited_data_script(__DIR__ . '/chinese_pinyin.txt', 'chinese_pinyin_import.sql', 'kmw_chinese_pinyin', null, false)) === FALSE) {
        fail("Failed to build chinese_pinyin sql");
      }

      if(($v = $this->create_tab_delimited_data_script(__DIR__ . '/japanese.txt', 'japanese_import.sql', 'kmw_japanese', null, false)) === FALSE) {
        fail("Failed to build japanese sql");
      }

      return true;
    }
  }
?>