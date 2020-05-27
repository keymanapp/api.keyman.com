<?php
  require_once('common.php');

  class build_models_sql extends build_common {

    private $models_path, $cache_path;

    function execute($data_root, $do_force) {
      $this->models_path = $data_root . '/model_info/';
      $this->cache_path = $data_root;
      $this->force = $do_force;

      if(!is_dir($this->cache_path)) {
        mkdir($this->cache_path, 0777, true) || fail("Unable to create folder " . $this->cache_path);
      }

      if(is_dir($this->models_path)) {
        deleteDir($this->models_path) || fail("Unable to remove folder " . $this->models_path);
      }

      if(!is_dir($this->models_path)) {
        mkdir($this->models_path, 0777, true) || fail("Unable to create folder " . $this->models_path);
      }

      reportTime();

      cache(URI_MODEL_INFO_ZIP, $this->cache_path . 'model_info.zip', 60 * 60 * 24 * 7, $this->force) || fail("Unable to download model_info.zip");

      $this->unzip() || fail("Unable to extract model_info.zip");

      if(($v = $this->build()) === false)
        fail("Unable to build models.sql");
      file_put_contents($this->cache_path . "models.sql", $v) || fail("Unable to write models.sql to " . $this->cache_path);

      reportTime();

      return true;
    }

    /**
      Build a SQL script to insert model_info data into the database
    */

    private $models = array();

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

      /* Transform all BCP47 to lower case */
      if(isset($model->languages)) {
        if(is_array($model->languages)) {
          $model->languages = array_map('strtolower', $model->languages);
        } else {
          $temp = (array)$model->languages;
          $model->languages = (object)array_combine(array_map('strtolower', array_keys($temp)), $temp);
        }
      }
      $json = json_encode($model, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
      $model->json = $json;

      array_push($this->models, $model);
      return true;
    }

    /**
      Generate an SQL script to insert entries in to the t_model table
    */
    function generate_model_inserts() {
      $insert = <<<END
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

      $result = '';
      foreach($this->models as $model) {
        $isRTL = isset($model->isRTL) && $model->isRTL;

        if(isset($model->minKeymanVersion) && preg_match('/^(\d+)\.(\d+)/', $model->minKeymanVersion, $matches)) {
          $minKeymanVersion1 = $matches[1];
          $minKeymanVersion2 = $matches[2];
        } else {
          $minKeymanVersion1 = 12;
          $minKeymanVersion2 = 0;
        }

        $includesFonts = isset($model->packageIncludes) && in_array('fonts', $model->packageIncludes);

        $result .= <<<END
$insert
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

          {$this->sqlb(null, $isRTL)},

          {$this->sqlb(null, $includesFonts)},

          {$this->sqlv($model, 'json')});
GO
END;
      }

      return $result;
    }

    /**
      Generate an SQL script to insert entries in to the t_model_language table
    */
    function generate_model_language_inserts() {
      $insert = <<<END
        INSERT t_model_language (
          model_id,
          bcp47,
          language_id,
          region_id,
          script_id
        ) VALUES
END;

      $result = '';
      foreach($this->models as $model) {
        foreach($model->languages as $id) {
          $this->parse_bcp47($id, $lang, $region, $script);
          $result .= <<<END
$insert
              ({$this->sqlv($model, 'id')},
              {$this->sqlv(null, strtolower($id))},
              {$this->sqlv(null, $lang)},
              {$this->sqlv(null, $region)},
              {$this->sqlv(null, $script)});
GO
END;
        }
      }

      return $result;
    }

    /**
      Generate an SQL script to insert entries in to the t_model_link table
    */
    function generate_model_link_inserts() {
      $insert = <<<END
        INSERT t_model_link (
          model_id,
          url,
          name
        ) VALUES
END;

      $result = '';
      foreach($this->models as $model) {
        if(!isset($model->links)) continue;
        foreach($model->links as $link) {
          $result .= <<<END
$insert
          ({$this->sqlv($model, 'id')},
          {$this->sqlv($link, 'url')},
          {$this->sqlv($link, 'name')});
GO
END;
        }
      }

      return $result;
    }

    /**
      Generate an SQL script to insert entries in to the t_model_related table
    */
    function generate_model_related_inserts() {
      $insert = <<<END
        INSERT t_model_related (
          model_id,
          related_model_id,
          deprecates
        ) VALUES
END;

      $result = '';
      foreach($this->models as $model) {
        if(!isset($model->related)) continue;
        $relatedobj = get_object_vars($model->related);
        foreach($relatedobj as $id => $related) {
          $deprecates = isset($related->deprecates) && $related->deprecates;
          $result .= <<<END
$insert
          ({$this->sqlv($model, 'id')},
          {$this->sqlv(null, $id)},
          {$this->sqlb(null, $deprecates)});
END;
        }
      }

      return $result;
    }


  }
?>