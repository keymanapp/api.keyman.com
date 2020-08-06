<?php

namespace {

  if (file_exists(dirname(__FILE__) . '/localenv.php')) {
    require_once(dirname(__FILE__) . '/localenv.php');
  }

  if (!isset($mysqlpw))
    $mysqlpw = isset($_SERVER['api_keyman_com_mssql_pw']) ? $_SERVER['api_keyman_com_mssql_pw'] : null;
  if (!isset($mysqluser))
    $mysqluser = isset($_SERVER['api_keyman_com_mssql_user']) ? $_SERVER['api_keyman_com_mssql_user'] : null;

  if (!isset($mssqldb)) $mssqldb = $_SERVER['api_keyman_com_mssqldb'];
  if (!isset($mssqlconninfo)) $mssqlconninfo = $_SERVER['api_keyman_com_mssqlconninfo'];
  if (!isset($mssql_create_database) && isset($_SERVER['api_keyman_com_mssql_create_database']))
    $mssql_create_database = $_SERVER['api_keyman_com_mssql_create_database'];

  class DatabaseConnectionInfo
  {
    const SCHEMA0 = 'k0', SCHEMA1 = 'k1';

    private $activeSchema;

    private function filename()
    {
      return dirname(__FILE__) . '/activeschema.txt';
    }

    function __construct()
    {
      if (file_exists($this->filename())) {
        $this->activeSchema = trim(file_get_contents($this->filename()));
      } else {
        $this->activeSchema = self::SCHEMA0;
      }
    }

    function getActiveSchema()
    {
      return $this->activeSchema;
    }

    function getInactiveSchema()
    {
      return $this->activeSchema == self::SCHEMA0 ? self::SCHEMA1 : self::SCHEMA0;
    }

    function setActiveSchema($value)
    {
      assert($value == self::SCHEMA0 || $value == self::SCHEMA1);
      file_put_contents($this->filename(), $value);
      $this->activeSchema = $value;
    }

    function getConnectionString() {
      global $mssqlconninfo, $mssqldb;
      return $mssqlconninfo . $mssqldb;
    }

    function getMasterConnectionString() {
      global $mssqlconninfo;
      return $mssqlconninfo . 'master';
    }

    function getDatabase() {
      global $mssqldb;
      return $mssqldb;
    }

    function getUser() {
      global $mysqluser;
      return $mysqluser;
    }

    function getPassword() {
      global $mysqlpw;
      return $mysqlpw;
    }
  }
}
