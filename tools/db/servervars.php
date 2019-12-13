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
