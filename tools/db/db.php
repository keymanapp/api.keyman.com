<?php
  require_once('servervars.php');
  $mysql = new mysqli($mysqlhost, $mysqluser, $mysqlpw, $mysqldb);
  $mysql->set_charset('utf8');
?>