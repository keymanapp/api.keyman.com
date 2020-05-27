<?php
  if(file_exists(dirname(__FILE__) . '/localenv.php')) {
    require_once(dirname(__FILE__) . '/localenv.php');
  }
  if(!isset($mysqlpw)) $mysqlpw=$_SERVER['api_keyman_com_mysql_pw'];
  if(!isset($mysqluser)) $mysqluser=$_SERVER['api_keyman_com_mysql_user'];
  if(!isset($mysqlhost)) $mysqlhost=$_SERVER['api_keyman_com_mysql_host'];
  if(!isset($mysqldb)) $mysqldb="keyboards";

  define('URI_KEYBOARD_INFO_ZIP', $_SERVER['api_keyman_com_keyboard_info_zip']);
  define('URI_MODEL_INFO_ZIP', $_SERVER['api_keyman_com_model_info_zip']);

  class ActiveDB {
    private $activedb;
    private function filename() {
      return dirname(__FILE__) . '/activedb.txt';
    }

    function __construct() {
      global $mssqldb0;
      if(file_exists($this->filename())) {
        $this->activedb = trim(file_get_contents($this->filename()));
      } else {
        $this->activedb = $mssqldb0;
      }

    }
    function get() {
      return $this->activedb;
    }

    function get_swap() {
      global $mssqldb0, $mssqldb1;
      return ($this->activedb == $mssqldb0) ? $mssqldb1 : $mssqldb0;
    }

    function set($value) {
      global $mssqldb0, $mssqldb1;
      assert($value == $mssqldb0 || $value == $mssqldb1);
      file_put_contents($this->filename(), $value);
      $this->activedb = $value;
    }
  }

  $activedb = new ActiveDB();