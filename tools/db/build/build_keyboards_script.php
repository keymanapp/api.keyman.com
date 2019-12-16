<?php  
  require_once('common.php');
  
  class build_keyboards_sql {
    
    private $keyboards_path, $cache_path;
    
    function execute($data_root, $do_force) {    
      $this->keyboards_path = $data_root . '/keyboard_info/';
      $this->cache_path = $data_root;
      $this->force = $do_force;
      
      if(!is_dir($this->cache_path)) {
        mkdir($this->cache_path, 0777, true) || fail("Unable to create folder " . $this->cache_path);
      }

      if(is_dir($this->keyboards_path)) {
        deleteDir($this->keyboards_path) || fail("Unable to remove folder " . $this->keyboards_path);
      }

      if(!is_dir($this->keyboards_path)) {
        mkdir($this->keyboards_path, 0777, true) || fail("Unable to create folder " . $this->keyboards_path);
      }

      cache(URI_KEYBOARD_INFO_ZIP, $this->cache_path . 'keyboard_info.zip', 60 * 60 * 24 * 7, $this->force) || fail("Unable to download keyboard_info.zip");
      
      $this->unzip() || fail("Unable to extract keyboard_info.zip");
      
      if(($v = $this->build()) === false) 
        fail("Unable to build keyboards.sql");
      file_put_contents($this->cache_path . "keyboards.sql", $v) || fail("Unable to write keyboards.sql to " . $this->cache_path);
      
      return true;
    }

    /**
      Build a SQL script to insert keyboard_info data into the database
    */

    private $keyboards = array();
    private $link;
    
    function unzip() {
      $zip = new ZipArchive();
      if(!$zip->open($this->cache_path . 'keyboard_info.zip', 0)) 
        return false;
      
      $result = $zip->extractTo($this->keyboards_path);
      $result |= $zip->close();
      
      return $result;
    }

    /**
      Search through all folders under keyboards_path to find .keyboard_info files to
      import into the database
    */
    function build() {
      $this->link = new mysqli();

      if(empty($this->keyboards_path)) {
        return false;
      }
      
      $files = glob($this->keyboards_path . '*.keyboard_info');
      foreach($files as $file) {
        if(!$this->process_file($file)) {
          return false;
        }
      }
      
      return 
        $this->generate_keyboard_inserts() .
        $this->generate_keyboard_language_inserts() .
        $this->generate_keyboard_link_inserts() .
        $this->generate_keyboard_related_inserts();
    }
    
    /**
      Loads a .keyboard_info file, parses it into the 
      keyboards array
    */
    function process_file($file) {
      if(($data = file_get_contents($file)) === false) {
        return false;
      }
      
      $keyboard = json_decode($data);

      /* Transform all BCP47 to lower case */
      if(isset($keyboard->languages)) {
        $temp = (array)$keyboard->languages;
        $keyboard->languages = (object)array_combine(array_map('strtolower', array_keys($temp)), $temp);
      }
      $json = json_encode($keyboard, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
      $keyboard->json = $json;
      
      array_push($this->keyboards, $keyboard);     
      return true;
    }
    
    /**
      Generate an SQL script to insert entries in to the t_keyboard table
    */
    function generate_keyboard_inserts() {
      $result = <<<END
        INSERT t_keyboard (
          keyboard_id, 
          name, 
          author_name, 
          author_email, 
          description, 
          license, 
          last_modified, 
          version,

          min_keyman_version,
          min_keyman_version_1,
          min_keyman_version_2,
          legacy_id,
          
          package_filename,
          package_filesize,
          js_filename,
          js_filesize,
          documentation_filename,
          documentation_filesize,

          is_rtl,
          is_unicode,
          is_ansi,
          
          includes_welcome,
          includes_documentation,
          includes_fonts,
          includes_visual_keyboard,
          
          platform_windows,
          platform_macos,
          platform_ios,
          platform_android,
          platform_web,
          
          keyboard_info
        ) VALUES
END;

      $comma = '';
      foreach($this->keyboards as $keyboard) {
        $isUnicode = isset($keyboard->encodings) && in_array('unicode', $keyboard->encodings);
        $isANSI = isset($keyboard->encodings) && in_array('ansi', $keyboard->encodings);
        $isRTL = isset($keyboard->isRTL) && $keyboard->isRTL;
        
        if(isset($keyboard->minKeymanVersion) && preg_match('/^(\d+)\.(\d+)/', $keyboard->minKeymanVersion, $matches)) {
          $minKeymanVersion1 = $matches[1];
          $minKeymanVersion2 = $matches[2];
        } else {
          $minKeymanVersion1 = 9;
          $minKeymanVersion2 = 0;
        }

        $includesWelcome = isset($keyboard->packageIncludes) && in_array('welcome', $keyboard->packageIncludes);
        $includesDocumentation = isset($keyboard->packageIncludes) && in_array('documentation', $keyboard->packageIncludes);
        $includesFonts = isset($keyboard->packageIncludes) && in_array('fonts', $keyboard->packageIncludes);
        $includesVisualKeyboard = isset($keyboard->packageIncludes) && in_array('visualKeyboard', $keyboard->packageIncludes);
        
        $platform_windows = isset($keyboard->platformSupport->windows) && $keyboard->platformSupport->windows != 'none';
        $platform_macos = isset($keyboard->platformSupport->macos) && $keyboard->platformSupport->macos != 'none';
        $platform_ios = isset($keyboard->platformSupport->ios) && $keyboard->platformSupport->ios != 'none';
        $platform_android = isset($keyboard->platformSupport->android) && $keyboard->platformSupport->android != 'none';
        $platform_web = isset($keyboard->platformSupport->desktopWeb) && $keyboard->platformSupport->desktopWeb != 'none'; // todo split into desktopWeb mobileWeb

        $result .= <<<END
$comma
          ({$this->sqlv($keyboard, 'id')},
          {$this->sqlv($keyboard, 'name')},
          {$this->sqlv($keyboard, 'authorName')},
          {$this->sqlv($keyboard, 'authorEmail')},
          {$this->sqlv($keyboard, 'description')},
          {$this->sqlv($keyboard, 'license')},
          {$this->sqld($keyboard, 'lastModifiedDate')},
          {$this->sqlv($keyboard, 'version')},
          
          {$this->sqlv($keyboard, 'minKeymanVersion')},
          $minKeymanVersion1,
          $minKeymanVersion2,
          {$this->sqli($keyboard, 'legacyId')},
    
          {$this->sqlv($keyboard, 'packageFilename')},
          {$this->sqli($keyboard, 'packageFileSize')},
          {$this->sqlv($keyboard, 'jsFilename')},
          {$this->sqli($keyboard, 'jsFileSize')},
          {$this->sqlv($keyboard, 'documentationFilename')},
          {$this->sqli($keyboard, 'documentationFileSize')},

          {$this->sqlb($isRTL)},
          {$this->sqlb($isUnicode)},
          {$this->sqlb($isANSI)},
    
          {$this->sqlb($includesWelcome)},
          {$this->sqlb($includesDocumentation)},
          {$this->sqlb($includesFonts)},
          {$this->sqlb($includesVisualKeyboard)},
    
          {$this->sqlb($platform_windows)},
          {$this->sqlb($platform_macos)},
          {$this->sqlb($platform_ios)},
          {$this->sqlb($platform_android)},
          {$this->sqlb($platform_web)},

          {$this->sqlv($keyboard, 'json')})
END;
        $comma=',';
      }

      if($comma == '') return ''; // no entries found
      return $result . ";\n";
    }
    
    /**
      Generate an SQL script to insert entries in to the t_keyboard_language table
    */
    function generate_keyboard_language_inserts() {
      $result = <<<END
        INSERT t_keyboard_language (
          keyboard_id, 
          bcp47, 
          language_id,
          region_id,
          script_id
        ) VALUES
END;

      $comma = '';
      foreach($this->keyboards as $keyboard) {
        if(is_array($keyboard->languages)) {
          $array = $keyboard->languages;
        } else {
          $array = array_keys(get_object_vars($keyboard->languages));
        }
        foreach($array as $id) {
          $this->parse_bcp47($id, $lang, $region, $script);
          $result .= <<<END
$comma
              ({$this->sqlv($keyboard, 'id')},
              {$this->sqlv(null, strtolower($id))},
              {$this->sqlv(null, $lang)},
              {$this->sqlv(null, $region)},
              {$this->sqlv(null, $script)})
END;
          $comma = ',';
        }
      }
      
      if($comma == '') return ''; // no entries found
      return $result . ";\n";
    }

    /**
      Generate an SQL script to insert entries in to the t_keyboard_link table
    */
    function generate_keyboard_link_inserts() {
      $result = <<<END
        INSERT t_keyboard_link (
          keyboard_id, 
          url, 
          name
        ) VALUES
END;

      $comma = '';
      foreach($this->keyboards as $keyboard) {
        if(!isset($keyboard->links)) continue;
        foreach($keyboard->links as $link) {
          $result .= <<<END
$comma
          ({$this->sqlv($keyboard, 'id')},
          {$this->sqlv($link, 'url')},
          {$this->sqlv($link, 'name')})
END;
          $comma = ',';
        }
      }
      
      if($comma == '') return ''; // no entries found
      return $result . ";\n";
    }

    /**
      Generate an SQL script to insert entries in to the t_keyboard_related table
    */
    function generate_keyboard_related_inserts() {
      $result = <<<END
        INSERT t_keyboard_related (
          keyboard_id, 
          related_keyboard_id, 
          deprecates
        ) VALUES
END;

      $comma = '';
      foreach($this->keyboards as $keyboard) {
        if(!isset($keyboard->related)) continue;
        $relatedobj = get_object_vars($keyboard->related);
        foreach($relatedobj as $id => $related) {
          $deprecates = isset($related->deprecates) && $related->deprecates;
          $result .= <<<END
$comma
          ({$this->sqlv($keyboard, 'id')},
          {$this->sqlv(null, $id)},
          {$this->sqlb($deprecates)})
END;
          $comma = ',';
        }
      }
      
      if($comma == '') return ''; // no entries found
      return $result . ";\n";
    }
    
    /**
      Safe-quotes a SQL string
    */
    function sqlv($o, $s) {
      if($o !== null) {
        if(isset($o->$s)) $s = $o->$s; 
        else return 'null';
      }
      if($s === null) return 'null';
      
      $v = strpos($s, "\0");
      if($v !== FALSE) {
        $s = substr($s, 0, strpos($s, "\0"));
      }
      $s = iconv("UTF-8", "UTF-8//IGNORE", $s); // Strip invalid UTF-8 characters
      //return "'" . mysql_real_escape_string($s) . "'";
      return 
        "'" . 
        str_replace(["'",   "\"",   "\r",  "\n",  "\b",  "\t"], 
                    ["\\'", "\\\"", "\\r", "\\n", "\\b", "\\t"],  
                    str_replace("\\", "\\\\", $s) ) . 
        "'";
    }

    /**
      Safe-quotes a SQL date
    */
    function sqld($o, $s) {
      if(isset($o->$s)) $s = $o->$s; 
      else return 'null';
      if($s === null) return 'null';
      $s = substr($s, 0, 19);
      return $this->sqlv(null, $s);
    }
    
    function sqli($o, $s) {
      if(isset($o->$s)) $s = $o->$s; 
      else return 'null';
      
      if(!is_numeric($s)) die('Expecting numeric $s');
      return $s;
    }
    
    function sqlb($b) {
      return $b ? '1' : '0';
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
  }
?>