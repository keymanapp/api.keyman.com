<?php
  if(file_exists(dirname(__FILE__) . '/localenv.php')) {
    require_once(dirname(__FILE__) . '/localenv.php');
  }

  if(!isset($mysqlpw))
    $mysqlpw=isset($_SERVER['api_keyman_com_mssql_pw']) ? $_SERVER['api_keyman_com_mssql_pw'] : null;
  if(!isset($mysqluser))
    $mysqluser=isset($_SERVER['api_keyman_com_mssql_user']) ? $_SERVER['api_keyman_com_mssql_user'] : null;

  if(!isset($mssqldb0)) $mssqldb0=$_SERVER['api_keyman_com_mssqldb0'];
  if(!isset($mssqldb1)) $mssqldb1=$_SERVER['api_keyman_com_mssqldb1'];
  if(!isset($mssqlconninfo)) $mssqlconninfo=$_SERVER['api_keyman_com_mssqlconninfo'];
  if(!isset($mssql_create_databases) && isset($_SERVER['api_keyman_com_mssql_create_databases'])) $mssql_create_databases=$_SERVER['api_keyman_com_mssql_create_databases'];
  $mssql_full_text_search = true; //!empty($mssql_full_text_search) || isset($_SERVER['api_keyman_com_full_text_search']);

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