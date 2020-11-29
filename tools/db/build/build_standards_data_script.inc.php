<?php
  require_once(__DIR__ . '/common.inc.php');
  require_once(__DIR__ . '/build_langtags_data_script.inc.php');
  require_once(__DIR__ . '/datasources.inc.php');

  class build_sql_standards_data extends build_common {

    function execute($data_root, $do_force) {
      $this->script_path = $data_root;
      $this->force = $do_force;

      if(!is_dir($this->script_path)) {
        mkdir($this->script_path, 0777, true) || fail("Unable to create folder {$this->script_path}");
      }

      reportTime();

      if(($v = $this->build_sql_data_script_subtags()) === FALSE) {
        fail("Failed to build language subtag registry sql");
      }

      file_put_contents($this->script_path . "language-subtag-registry.sql", $v) || fail("Unable to write language-subtag-registry.sql to {$this->script_path}");

      reportTime();

      if(!$this->cache_iso639_3_file($this->DBDataSources->uriIso6393, 'iso639-3.tab', 'iso639-3.sql', 't_iso639_3',
        [ 'Id', 'Part2B', 'Part2T', 'Part1', '_Scope', '_Type', 'Ref_Name', '_Comment' ])) {
        // include fields because of inconsistent row lengths
        fail("Failed to download iso639-3.tab");
      }

      reportTime();

      if(!$this->cache_iso639_3_file($this->DBDataSources->uriIso6393NameIndex, 'iso639-3_Name_Index.tab', 'iso639-3-name-index.sql', 't_iso639_3_names',
        [ 'Id', 'Print_Name', 'Inverted_Name' ])) {
        fail("Failed to download iso639-3_Name_Index.tab");
      }

      reportTime();

      if(!$this->cache_ethnologue_language_index($this->DBDataSources->uriEthnologueLanguageCodes, 'ethnologue_language_codes.tab', 'ethnologue_language_codes.sql', 't_ethnologue_language_codes')) {
        fail("Failed to download ethnologue_language_codes.tab");
      }

      reportTime();

      if(!$this->cache_ethnologue_language_index($this->DBDataSources->uriEthnologueCountryCodes, 'ethnologue_country_codes.tab', 'ethnologue_country_codes.sql', 't_ethnologue_country_codes')) {
        fail("Failed to download ethnologue_country_codes.tab");
      }

      reportTime();

      if(!$this->cache_ethnologue_language_index($this->DBDataSources->uriEthnologueLanguageIndex, 'ethnologue_language_index.tab', 'ethnologue_language_index.sql', 't_ethnologue_language_index')) {
        fail("Failed to download ethnologue_language_index.tab");
      }

      reportTime();

      $langtags = new build_sql_standards_data_langtags($this->DBDataSources, $this->schema);
      $langtags->execute($data_root, $do_force);

      return true;
    }

    /*
     * Downloads an Ethnologue file and builds a script to import it.
     */

    function cache_ethnologue_language_index($url, $tabfilename, $sqlfilename, $table) {
      return $this->cache_tab_delimited_data($url, $tabfilename, $sqlfilename, $table);
    }

    /*
     * Downloads an ISO639-3 file and builds a script to import it.
     */

    function cache_iso639_3_file($url, $tabfilename, $sqlfilename, $table, $columns) {
      return $this->cache_tab_delimited_data($url, $tabfilename, $sqlfilename, $table, $columns);
    }


    private $languages = array();
    private $scripts = array();
    private $regions = array();

    /**
     * Build a SQL script to insert language-subtag-registry data into the database
     */
    function build_sql_data_script_subtags() {
      $cache_file = $this->script_path . "language-subtag-registry";
      if(!cache($this->DBDataSources->uriLanguageSubtagRegistry, $cache_file, 60 * 60 * 24 * 7, $this->force)) {
        return false;
      }

      if(($file = file($cache_file, FILE_IGNORE_NEW_LINES)) === FALSE) {
        return false;
      }
      if(($file = $this->unwrap($file)) === FALSE) {
        return false;
      }
      if(!$this->process_subtag_file($file)) {
        return false;
      }

      return
        $this->generate_language_inserts() .
        $this->generate_language_index_inserts() .
        $this->generate_script_inserts() .
        $this->generate_region_inserts();
    }

    /**
     * language-subtag-registry wraps long lines with a two-space prefix on
     * subsequent lines. So easiest to unwrap those lines before processing.
     */
    function unwrap($array) {
      $p = '';
      for($i = sizeof($array)-1; $i >= 0; $i--) {
        if(substr($array[$i], 0, 2) == '  ') {
          $p = substr($array[$i], 2, 1024) . ' ' . trim($p);
          $array[$i] = 'WRAP:'.$array[$i];
        } elseif(!empty($p)) {
          $array[$i] .= ' ' . trim($p);
          $p = '';
        }
      }
      return $array;
    }

    /**
     * Loads the entries we are interested in from the language-subtag-registry
     * into arrays for processing.
     */
    function process_subtag_file($file) {
      $row = array();
      foreach($file as $line) {
        $line = trim($line);
        if($line == '%%') {
          if(!empty($row)) $this->process_entry($row);
          $row = array();
          continue;
        }
        if($line == '') continue;

        $v = explode(':', $line);
        $id = $v[0]; $v = trim($v[1]);
        if(array_key_exists($id, $row)) {
          $this->to_array($row, $id);
          array_push($row[$id], $v);
        } else {
          $row[$id] = $v;
        }
      }

      return true;
    }

    /**
     * Processes a single entry, as delimited by %% in the language-subtag-registry,
     * and adds it to the appropriate array. At this time, we are only interested in
     * the subtag and the description(s) for the given entry.
     */
    function process_entry($row) {
      if(!isset($row['Type'])) return;
      $this->to_array($row, 'Description');
      if($row['Description'][0] == 'Private use') {
        // no 'scope' set for script, region private use descriptive subtags
        // We don't want the "private use" subtags as they are a range rather than
        // a single subtag
        return;
      }
      if(isset($row['Scope']) && $row['Scope'] == 'private-use') return;

      // We'll work with all subtags as lower case for search etc
      if(!isset($row['Subtag'])) return;
      $subtag = strtolower($row['Subtag']);

      switch($row['Type']) {
      case 'language':
        $this->languages[$subtag] = $row['Description'];
        break;
      case 'script':
        $this->scripts[$subtag] = $row['Description'];
        break;
      case 'region':
        $this->regions[$subtag] = $row['Description'];
        break;
      }
    }

    /**
     * Generate an SQL script to insert entries in to the t_language table
     */
    function generate_language_inserts() {
      $result = "" ;

      $comma='';
      foreach($this->languages as $lang => $detail) {
        $result .= "INSERT t_language (language_id) VALUES({$this->sqlv(null,$lang)})\n";
      }
      return $result;
    }

    /**
     * Generate an SQL script to insert entries in to the t_language_index table
     */
    function generate_language_index_inserts() {
      $result = "";
      foreach($this->languages as $lang => $detail) {
        foreach($detail as $name) {
          $result .= "INSERT t_language_index (language_id, name) VALUES ({$this->sqlv(null,$lang)},{$this->sqlv(null,$name)})\n";
        }
      }
      return $result;
    }

    /**
     * Generate an SQL script to insert entries in to the t_script table
     */
    function generate_script_inserts() {
      $result = "";

      foreach($this->scripts as $script => $detail) {
        $result .= "INSERT t_script (script_id, name) VALUES ({$this->sqlv(null,$script)},{$this->sqlv(null,$detail[0])})\n";
      }
      return $result;
    }

    /**
     * Generate an SQL script to insert entries in to the t_region table
     */
    function generate_region_inserts() {
      $result = "";

      foreach($this->regions as $region => $detail) {
        $result .= "INSERT t_region (region_id, name) VALUES ({$this->sqlv(null,$region)},{$this->sqlv(null,$detail[0])})\n";
      }
      return $result;
    }

    /**
     * Helper function to convert a value into an array if it isn't already
     */
    function to_array(&$row, $id) {
      if(!is_array($row[$id])) $row[$id] = array($row[$id]);
    }

  }
?>