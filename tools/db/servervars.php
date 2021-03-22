<?php

namespace {

  if (file_exists(dirname(__FILE__) . '/localenv.php')) {
    require_once(dirname(__FILE__) . '/localenv.php');
  }

  # For now, we don't use autoload for this file
  require_once(dirname(__FILE__) . '/../../_common/KeymanHosts.php');
  use \Keyman\Site\Common\KeymanHosts;

  if (!isset($mssqlpw))
    $mssqlpw = isset($_SERVER['api_keyman_com_mssql_pw']) ? $_SERVER['api_keyman_com_mssql_pw'] : null;
  if (!isset($mssqluser))
    $mssqluser = isset($_SERVER['api_keyman_com_mssql_user']) ? $_SERVER['api_keyman_com_mssql_user'] : null;

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

    private function getSchemaPrefix() {
      return (KeymanHosts::Instance()->Tier() == KeymanHosts::TIER_PRODUCTION) ? 'production_' : '';
    }

    function getActiveSchema()
    {
      return $this->getSchemaPrefix() . $this->activeSchema;
    }

    function getInactiveSchema()
    {
      return $this->getSchemaPrefix() . ($this->activeSchema == self::SCHEMA0 ? self::SCHEMA1 : self::SCHEMA0);
    }

    function setActiveSchema($value)
    {
      // Strip off the schema prefix
      $value = substr($value, strlen($this->getSchemaPrefix()));

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
      global $mssqluser;
      return $mssqluser;
    }

    function getPassword() {
      global $mssqlpw;
      return $mssqlpw;
    }
  }
}
