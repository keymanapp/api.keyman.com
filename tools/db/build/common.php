<?php
  /*
   Caches the URL into the file path; if the file is older than
   two weeks, tries again. If it fails to download, uses the cached
   version; if no file can be retrieved then dies.
  */
  function cache($url, $filename, $duration = 60 * 60 * 24 * 7, $do_force = false) {
    global $force;
    $local_force = $force || $do_force;
    if($local_force) $duration = 0;
    if(!file_exists($filename) || time()-filemtime($filename) > $duration) {
      echo "Downloading $url\n";
      
      // Create a stream
      $opts = array(
        'http'=>array(
          'method'=>"GET",
          'header'=>"User-Agent: api-keyman-com Mozilla/5.0 (iPad; CPU OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1\r\n"
        )
      );      

      $context = stream_context_create($opts);      

      if(($file = @file_get_contents($url, false, $context)) === FALSE) {
        if(!file_exists($filename)) {
          echo "Failed to download $url: $php_errormsg\n"; //todo to stderr
          return false;
        }
      } else {
        file_put_contents($filename, $file);
      }
    }
    return true;
  }  

  /*
   Recursively delete files from a folder plus the folder itself
  */
  function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
      throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
      $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
      if (is_dir($file)) {
        if(!deleteDir($file)) return false;
      } else {
        if(!unlink($file)) return false;
      }
    }
    return rmdir($dirPath);
  }
