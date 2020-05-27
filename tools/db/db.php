<?php
  require_once('servervars.php');

  try {
    $mssql = new PDO($mssqlconninfo . $activedb->get(), $mysqluser, $mysqlpw, NULL);
    $mssql->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
  }
  catch( PDOException $e ) {
    die( "Error connecting to SQL Server: " . $e );
 }

?>