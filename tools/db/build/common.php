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



    /**
     * Safe-quotes a SQL string
     */
    function sqlv($o, $s) {
      if($o !== null) {
        if(isset($o->$s)) $s = $o->$s;
        else return 'null';
      }
      return sqlv0($s);
    }

    function sqlv0($s) {
      if($s === null) return 'null';

      $v = strpos($s, "\0");
      if($v !== FALSE) {
        $s = substr($s, 0, strpos($s, "\0"));
      }
      $s = iconv("UTF-8", "UTF-8//IGNORE", $s); // Strip invalid UTF-8 characters
      return "'" . str_replace("'", "''", $s) . "'";
    }

    /**
     * Safe-quotes a SQL date
     */
    function sqld($o, $s) {
      if($o !== null) {
        if(isset($o->$s)) $s = $o->$s;
        else return 'null';
      }
      return sqld0($s);
    }

    function sqld0($s) {
      if($s === null) return 'null';
      $s = substr($s, 0, 19);
      return sqlv(null, $s);
    }

    function sqli($o, $s) {
      if($o !== null) {
        if(isset($o->$s)) $s = $o->$s;
        else return 'null';
      }
      return sqli0($s);
    }

    function sqli0($s) {
      if($s === null) return 'null';
      if(!is_numeric($s)) die('Expecting numeric $s');
      return (string) $s;
    }

    function sqlb($o, $s) {
      if($o !== null) {
        if(isset($o->$s)) $s = $o->$s;
        else return 'null';
      }

      return sqlb0($s);
    }

    function sqlb0($s) {
      if($s === null) return 'null';
      return $s ? '1' : '0';
    }

    class build_common {
      public $force, $script_path;

      function sqlv($o, $p) {
        return sqlv($o, $p);
      }

      function sqlv0($s) {
        return sqlv0($s);
      }

      function sqld($o, $p) {
        return sqld($o, $p);
      }

      function sqlb($o, $p) {
        return sqlb($o, $p);
      }

      function sqlb0($s) {
        return sqlb0($s);
      }

      function sqli($o, $p) {
        return sqli($o, $p);
      }

      function sqli0($s) {
        return sqli0($s);
      }

      function parse_bcp47($bcp47, &$lang, &$region, &$script) {
        $lang = null;
        $region = null;
        $script = null;

        // RegEx from https://stackoverflow.com/questions/7035825/regular-expression-for-a-language-tag-as-defined-by-bcp47, https://stackoverflow.com/a/34775980/1836776
        $re = preg_match("/^(?<grandfathered>(?:en-GB-oed|i-(?:ami|bnn|default|enochian|hak|klingon|lux|mingo|navajo|pwn|t(?:a[oy]|su))|sgn-(?:BE-(?:FR|NL)|CH-DE))|(?:art-lojban|cel-gaulish|no-(?:bok|nyn)|zh-(?:guoyu|hakka|min(?:-nan)?|xiang)))|(?:(?<language>(?:[A-Za-z]{2,3}(?:-(?<extlang>[A-Za-z]{3}(?:-[A-Za-z]{3}){0,2}))?)|[A-Za-z]{4}|[A-Za-z]{5,8})(?:-(?<script>[A-Za-z]{4}))?(?:-(?<region>[A-Za-z]{2}|[0-9]{3}))?(?:-(?<variant>[A-Za-z0-9]{5,8}|[0-9][A-Za-z0-9]{3}))*(?:-(?<extension>[0-9A-WY-Za-wy-z](?:-[A-Za-z0-9]{2,8})+))*)(?:-(?<privateUse>x(?:-[A-Za-z0-9]{1,8})+))?$/Di", $bcp47, $matches);
        if($re === FALSE) {
          return false;
        }

        if(isset($matches['language'])) $lang = strtolower($matches['language']);
        if(isset($matches['region'])) $region = strtolower($matches['region']);
        if(isset($matches['script'])) $script = strtolower($matches['script']);
        return true;
      }

      function cache_tab_delimited_data($url, $tabfilename, $sqlfilename, $table, $cols=null) {
        $cache_file = $this->script_path . $tabfilename;
        if(!cache($url, $cache_file, 60 * 60 * 24 * 7, $this->force)) {
          return false;
        }

        return $this->create_tab_delimited_data_script($cache_file, $sqlfilename, $table, $cols);
      }

      function create_tab_delimited_data_script($cache_file, $sqlfilename, $table, $cols=null, $chopFirstLine = true) {
        if(($data = file($cache_file, FILE_IGNORE_NEW_LINES | FILE_IGNORE_NEW_LINES)) === FALSE) {
          die("Unable to find $cache_file");
        }
        if($chopFirstLine) array_shift($data); // ignore first line
        $sql = '';
        $coldef = $cols ? '(' . implode(',', $cols) . ')' : '';

        $n = 0;

        foreach($data as $line) {
          $sql .= "INSERT $table $coldef VALUES(";
          $row = str_getcsv($line,"\t",'',"");
          $comma = '';
          foreach($row as $col) {
            $sql .= $comma.sqlv(null,$col);
            $comma = ',';
          }
          if($cols) {
            for($i = sizeof($row); $i < sizeof($cols); $i++) {
              $sql .= "{$comma}NULL";
              $comma = ',';
            }
          }
          $sql .= ")\n";
          if((++$n) % 100 == 0)
            $sql .= "GO\n";
        }

        file_put_contents($this->script_path . $sqlfilename, $sql) || fail("Unable to write $sqlfilename to {$this->script_path}");
        return true;
      }
    }
