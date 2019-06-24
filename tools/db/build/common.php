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
      if(($file = @file_get_contents($url)) === FALSE) {
        if(!file_exists($filename)) {
          echo "Failed to download $url\n"; //todo to stderr
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
?>