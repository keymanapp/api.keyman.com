<?php  
  require_once('common.php');
  
  class build_models_sql {
    
    private $models_path, $cache_path;
    
    function execute($data_root, $do_force) {    
      $this->models_path = $data_root . '/model_info/';
      $this->cache_path = $data_root;
      $this->force = $do_force;
      
      if(!is_dir($this->cache_path)) {
        mkdir($this->cache_path, 0777, true) || fail("Unable to create folder " . $this->cache_path);
      }
      
      if(!is_dir($this->models_path)) {
        mkdir($this->models_path, 0777, true) || fail("Unable to create folder " . $this->models_path);
      }

      cache(URI_MODEL_INFO_ZIP, $this->cache_path . 'model_info.zip', 60 * 60 * 24 * 7, $this->force) || fail("Unable to download model_info.zip");
      
      $this->unzip() || fail("Unable to extract model_info.zip");
      
      if(($v = $this->build()) === false) 
        fail("Unable to build models.sql");
      file_put_contents($this->cache_path . "models.sql", $v) || fail("Unable to write models.sql to " . $this->cache_path);
      
      return true;
    }

    /**
      Build a SQL script to insert model_info data into the database
    */

    private $models = array();
    private $link;
    
    function unzip() {
      $zip = new ZipArchive();
      if(!$zip->open($this->cache_path . 'model_info.zip', 0)) 
        return false;
      
      $result = $zip->extractTo($this->models_path);
      $result |= $zip->close();
      
      return $result;
    }

    /**
      Search through all folders under models_path to find .model_info files to
      import into the database
    */
    function build() {
      $this->link = new mysqli();

      if(empty($this->models_path)) {
        return false;
      }
      
      $files = glob($this->models_path . '*.model_info');
      foreach($files as $file) {
        if(!$this->process_file($file)) {
          return false;
        }
      }
      
      return 
        $this->generate_model_inserts() .
        $this->generate_model_language_inserts() .
        $this->generate_model_link_inserts() .
        $this->generate_model_related_inserts();
    }
    
    /**
      Loads a .model_info file, parses it into the 
      models array
    */
    function process_file($file) {
      if(($data = file_get_contents($file)) === false) {
        return false;
      }
      
      $model = json_decode($data);
      $model->json = $data;
      
      array_push($this->models, $model);     
      return true;
    }
    
    /**
      Generate an SQL script to insert entries in to the t_model table
    */
    function generate_model_inserts() {
      $result = <<<END
        INSERT t_model (
          model_id, 
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
          
          package_filename,
          package_filesize,
          js_filename,
          js_filesize,

          is_rtl,
          
          includes_fonts,
          
          model_info
        ) VALUES
END;

      $comma = '';
      foreach($this->models as $model) {
        $isRTL = isset($model->isRTL) && $model->isRTL;
        
        if(isset($model->minKeymanVersion) && preg_match('/^(\d+)\.(\d+)/', $model->minKeymanVersion, $matches)) {
          $minKeymanVersion1 = $matches[1];
          $minKeymanVersion2 = $matches[2];
        } else {
          $minKeymanVersion1 = 9;
          $minKeymanVersion2 = 0;
        }

        $includesFonts = isset($model->packageIncludes) && in_array('fonts', $model->packageIncludes);
        
        $result .= <<<END
$comma
          ({$this->sqlv($model, 'id')},
          {$this->sqlv($model, 'name')},
          {$this->sqlv($model, 'authorName')},
          {$this->sqlv($model, 'authorEmail')},
          {$this->sqlv($model, 'description')},
          {$this->sqlv($model, 'license')},
          {$this->sqld($model, 'lastModifiedDate')},
          {$this->sqlv($model, 'version')},
          
          {$this->sqlv($model, 'minKeymanVersion')},
          $minKeymanVersion1,
          $minKeymanVersion2,
    
          {$this->sqlv($model, 'packageFilename')},
          {$this->sqli($model, 'packageFileSize')},
          {$this->sqlv($model, 'jsFilename')},
          {$this->sqli($model, 'jsFileSize')},

          {$this->sqlb($isRTL)},
    
          {$this->sqlb($includesFonts)},

          {$this->sqlv($model, 'json')})
END;
        $comma=',';
      }

      if($comma == '') return ''; // no entries found
      return $result . ";\n";
    }
    
    /**
      Generate an SQL script to insert entries in to the t_model_language table
    */
    function generate_model_language_inserts() {
      $result = <<<END
        INSERT t_model_language (
          model_id, 
          bcp47, 
          language_id,
          region_id,
          script_id
        ) VALUES
END;

      $comma = '';
      foreach($this->models as $model) {
        foreach($model->languages as $id) {
          $this->parse_bcp47($id, $lang, $region, $script);
          $result .= <<<END
$comma
              ({$this->sqlv($model, 'id')},
              {$this->sqlv(null, $id)},
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
      Generate an SQL script to insert entries in to the t_model_link table
    */
    function generate_model_link_inserts() {
      $result = <<<END
        INSERT t_model_link (
          model_id, 
          url, 
          name
        ) VALUES
END;

      $comma = '';
      foreach($this->models as $model) {
        if(!isset($model->links)) continue;
        foreach($model->links as $link) {
          $result .= <<<END
$comma
          ({$this->sqlv($model, 'id')},
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
      Generate an SQL script to insert entries in to the t_model_related table
    */
    function generate_model_related_inserts() {
      $result = <<<END
        INSERT t_model_related (
          model_id, 
          related_model_id, 
          deprecates
        ) VALUES
END;

      $comma = '';
      foreach($this->models as $model) {
        if(!isset($model->related)) continue;
        $relatedobj = get_object_vars($model->related);
        foreach($relatedobj as $id => $model) {
          $deprecates = isset($related->deprecates) && $related->deprecates;
          $result .= <<<END
$comma
          ({$this->sqlv($model, 'id')},
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
      
      if(isset($matches['language'])) $lang = $matches['language'];
      if(isset($matches['region'])) $region = $matches['region'];
      if(isset($matches['script'])) $script = $matches['script'];
      return true;
    }
  }
?>