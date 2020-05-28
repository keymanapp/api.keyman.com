<?php
  require_once('../util.php');
  require_once('servervars.php');

  try {
    $mssql = new PDO($mssqlconninfo . $activedb->get(), $mysqluser, $mysqlpw, NULL);
    $mssql->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
  }
  catch( PDOException $e ) {
    fail( "Error connecting to SQL Server: " . $e->getMessage(), 500 );
  }
