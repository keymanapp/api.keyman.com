<?php
  require_once('common.php');

  //define('LANGTAGS', 'https://ldml.api.sil.org/langtags.json');
  // 2020-05-25: LANGTAGS 1.1.1 is currently in staging. Once it hits release, use link above instead.
  // We want the windows and suppress tags which are only in 1.1.1
  define('LANGTAGS', 'https://raw.githubusercontent.com/silnrsi/langtags/master/pub/langtags.json');

  class build_sql_standards_data_langtags extends build_common {

    function execute($data_root, $do_force) {
      $this->script_path = $data_root;
      $this->force = $do_force;

      if(!$this->cache_langtags(LANGTAGS, 'langtags.json')) {
        fail("Failed to download langtags.json");
      }
    }

    private function cache_langtags($url, $jsonFilename) {
      $cache_file = $this->script_path . $jsonFilename;
      $sqlfilename = $jsonFilename . '.sql';

      if(!cache($url, $cache_file, 60 * 60 * 24 * 7, $this->force)) {
        return false;
      }

      $json = json_decode(file_get_contents($cache_file));

      $sql = '';
      foreach($json as $obj) {
        switch($obj->tag) {
          case '_globalvar': $sql .= $this->process_globalvar($obj); break;
          case '_phonvar': $sql .= $this->process_phonvar($obj); break;
          case '_version': $sql .= $this->process_version($obj); break;
          default: $sql .= $this->process_entry($obj); break;
        }
      }

      file_put_contents($this->script_path . $sqlfilename, $sql) || fail("Unable to write $sqlfilename to {$this->script_path}");
      return true;
    }

    private function process_entry($obj) {

      // Add all basic elements to our object. We include the tag, full, windows tags in the main record as these are the master
      // ones. They will also be listed in the alternates in langtag_tag in order to simplify searches against them.

      $sql = "
        INSERT t_langtag (
          tag, [full], iso639_3, region,
          regionname, name, sldr,
          nophonvars, script, suppress, windows)
        SELECT
          {$this->sqlv($obj, 'tag')}, {$this->sqlv($obj, 'full')}, {$this->sqlv($obj, 'iso639_3')}, {$this->sqlv($obj, 'region')},
          {$this->sqlv($obj, 'regionname')}, {$this->sqlv($obj, 'name')}, {$this->sqlb($obj, 'sldr')},
          {$this->sqlb($obj, 'nophonvars')}, {$this->sqlv($obj, 'script')}, {$this->sqlb($obj, 'suppress')}, {$this->sqlv($obj, 'windows')};
      ";

      // Note: we don't add localname here as it's always in the localnames array

      // Add all names to our search index

      $names = isset($obj->names) ? $obj->names : [];
      if(array_search($obj->name, $names) === FALSE) array_unshift($names, $obj->name);
      $sql .= $this->process_entry_names(0, $obj->tag, $names);

      if(isset($obj->localnames)) $sql .= $this->process_entry_names(1, $obj->tag, $obj->localnames);
      if(isset($obj->latnname)) $sql .= $this->process_entry_names(2, $obj->tag, $obj->latnnames); // TODO we lose association with localnames here
      if(isset($obj->iana)) {
        if(is_array($obj->iana)) $iana = $obj->iana;
        else $iana = [$obj->iana];
        $sql .= $this->process_entry_names(3, $obj->tag, $iana);
      }

      // Add all tags to our search index

      $tags = isset($obj->tags) ? $obj->tags : [];
      if(array_search($obj->windows, $tags) === FALSE) array_unshift($tags, $obj->windows);
      if(array_search($obj->full, $tags) === FALSE) array_unshift($tags, $obj->full);
      if(array_search($obj->tag, $tags) === FALSE) array_unshift($tags, $obj->tag);
      $sql .= $this->process_entry_tags(0, $obj->tag, $tags);

      if(isset($obj->variants)) $sql .= $this->process_entry_tags(1, $obj->tag, $obj->variants);

      // Add regions to our search index
      $regions = isset($obj->regions) ? $obj->regions : [];
      if(isset($obj->region) && array_search($obj->region, $regions) === FALSE) array_unshift($regions, $obj->region);
      $sql .= $this->process_entry_regions($obj->tag, $regions);

      return $sql;
    }

    private function process_entry_names($nametype, $tag, $names) {
      $sql = '';
      foreach($names as $name) {
        $namekd = Normalizer::normalize($name, Normalizer::FORM_KD);
        $namekd = preg_replace('/\p{Mn}/u', '', $namekd);
        $sql .= "INSERT t_langtag_name (tag, name, name_kd, nametype) SELECT {$this->sqlv0($tag)}, {$this->sqlv0($name)}, {$this->sqlv0($namekd)}, {$this->sqlv0($nametype)};\n";
      }
      return $sql;
    }

    private function process_entry_tags($alttagtype, $tag, $alttags) {
      $sql = '';
      foreach($alttags as $alttag) {
        $sql .= "INSERT t_langtag_tag (tag, alttag, alttagtype) SELECT {$this->sqlv0($tag)}, {$this->sqlv0($alttag)}, {$this->sqlv0($alttagtype)};\n";
      }
      return $sql;
    }

    private function process_entry_regions($tag, $regions) {
      $sql = '';
      foreach($regions as $region) {
        $sql .= "INSERT t_langtag_region (tag, region) SELECT {$this->sqlv0($tag)}, {$this->sqlv0($region)};\n";
      }
      return $sql;
    }


    private function process_globalvar($obj) {
      return '';
    }

    private function process_phonvar($obj) {
      return '';
    }

    private function process_version($obj) {
      return '';
    }

  }
