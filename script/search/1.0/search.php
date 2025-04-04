<?php
  require_once('../../../tools/util.php');
  require_once __DIR__ . '/../../../tools/autoload.php';
  use Keyman\Site\Common\KeymanHosts;

  allow_cors();
  json_response();

  require_once('../../../tools/db/db.php');
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  header('Link: <' . KeymanHosts::Instance()->api_keyman_com . '/schemas/search/1.0/search.json#>; rel="describedby"');

  define('rmAll', 7);
  define('rmCountry', 1);
  define('rmLanguage', 2);
  define('rmKeyboard', 4);

  /*
    https://api.keyman.com/search?q=query-string

    Search for a keyboard. Returns a result that lists all keyboards, languages and countries that match.
    https://api.keyman.com/schemas/search.json is JSON schema

    @param q    query-string   a partial string to search for in keyboard name, id, description, language.
                               prefixes:  c:id:<id>   show languages for the country with ISO code <id>
                                          l:id:<id>   show keyboards for the language with BCP 47 code <id>
                                          k:id:<id>   show keyboard with the id <id>
                                          c:<text>    show only countries (regions)
                                          l:<text>    show only languages matching <text>
                                          k:<text>    show only keyboards
                                          k:legacy:<id> show keyboard with the legacy integer id <id>
  */

  if(!isset($_REQUEST['q'])) {
    fail('Query string must be set');
  }

  $q = trim($_REQUEST['q']);

  if($q == '') {
    fail('Query string must not be empty');
  }

  $s = new KeyboardSearch($mssql);
  if(isset($_REQUEST['platform'])) {
    $platform = $_REQUEST['platform'];
    if(in_array($platform, array('macos', 'windows', 'linux', 'android', 'ios', 'desktopWeb', 'mobileWeb'))) {
      $s->platform = $platform;
    }
  }
  $s->GetSearchMatches($q);
  $json = $s->WriteSearchResults();

  json_print($json);

  class KeyboardSearchResult {
    public $search; // search query has been entered
    public $rangematch, $isomatch, $allmatch, $regionmatch;
    public $text, $textparts, $searchtext, $region;
  }

  class KeyboardSearch {
    private $ksw, $result;
    private $redirection, $fNoRedirect;
    public $platform;
    private $mssql;

    function __construct($mssql) {
      $this->mssql = $mssql;
    }

    function GetSearchMatches($query) {
      $result = new KeyboardSearchResult();
      $result->search = false;
      $this->result = $result;

      $result->search = true;
      $result->rangematch = rmAll;
      $result->isomatch = false;
      $result->allmatch = false;
      $result->regionmatch = false;
      $result->legacy = false;

      $result->textparts = explode(':', $query);

      for($i = 0; $i < sizeof($result->textparts) - 1; $i++) {
        $match = strtolower($result->textparts[$i]);
        if(!strcmp($match, 'id')) $result->isomatch = true;
        else if(!strcmp($match, 'iso')) $result->isomatch = true;
        else if(!strcmp($match, 'all')) $result->allmatch = true;
        else if(!strcmp($match, 'region')) $result->regionmatch = true;
        else if(!strcmp($match, 'legacy')) $result->legacy = true;
        else if(!strncmp($match, 'country', strlen($match))) $result->rangematch = rmCountry;
        else if(!strncmp($match, 'language', strlen($match))) $result->rangematch = rmLanguage;
        else if(!strncmp($match, 'keyboard', strlen($match))) $result->rangematch = rmKeyboard;
      }

      if($result->isomatch && $result->rangematch == rmAll) {
        // Don't support match on ID for all contexts
        $result->isomatch = false;
      }

      $result->text = array_pop($result->textparts);
      $result->searchtext = $result->text;

      //$result->text = str_replace('*', '%', $result->text);
      //$result->text = str_replace('_', '?', $result->text);

      if($result->regionmatch) {
        /*$r = new CRM_Region();
        if(!$r->Load($result->searchtext)) $result->region='Unknown';
        else $result->region=$r->Name;*/
        $result->region='Unknown';
      }

      $this->result = $result;
      return true;
    }

    function GetSearchText() {
      $this->GetSearchQueries($rangetext, $count, $countries, $langs, $keyboards);
      return $this->result->searchtext;
    }

    private $gsq_complete=false, $gsq_rangetext, $gsq_count, $gsq_countries, $gsq_langs, $gsq_keyboards;

    private function GetSearchQueries(&$rangetext, &$count, &$countries, &$langs, &$keyboards) {
      if(!$this->gsq_complete) {
        $this->result->searchtext = strip_tags($this->result->searchtext);
        switch($this->result->rangematch) {
          case rmAll:
            $this->gsq_rangetext = ''; break;
            //$rangetext = "Matches found for '{$this->result->searchtext}'"; break;
          case rmKeyboard:
            if($this->result->isomatch) $this->gsq_rangetext = "Keyboard with id '{$this->result->searchtext}'";
            else if($this->result->legacy) $this->gsq_rangetext = "Keyboard with legacy id '{$this->result->searchtext}'";
            else $this->gsq_rangetext = "Keyboards matching '{$this->result->searchtext}'"; break;
          case rmLanguage:
            if($this->result->allmatch) $this->gsq_rangetext = "All languages matching '{$this->result->searchtext}'";
            else if($this->result->isomatch) $this->gsq_rangetext = "Keyboards for language with BCP 47 code '{$this->result->searchtext}'";
            else $this->gsq_rangetext = "Languages matching '{$this->result->searchtext}'"; break;
          case rmCountry:
            //if($this->result->regionmatch && $this->result->allmatch) $this->gsq_rangetext = "Countries in {$this->result->region}, all languages";
            //else
            if($this->result->regionmatch) $this->gsq_rangetext = "Countries in {$this->result->region}";
            //else if($this->result->allmatch && $this->result->isomatch) $this->gsq_rangetext = "All languages for country with ISO3166-1 code '{$this->result->searchtext}'";
            //else if($this->result->allmatch) $this->gsq_rangetext = "Countries matching '{$this->result->searchtext}'";
            else if($this->result->isomatch) $this->gsq_rangetext = "Languages for country with ISO3166-1 code '{$this->result->searchtext}'";
            else $this->gsq_rangetext = "Countries matching '{$this->result->searchtext}'"; break;
        }

        // Search for all language names that match - either name, dialect name, or alternate name

        $this->gsq_count = 0;

        if($this->result->isomatch) {
          switch($this->result->rangematch) {
            case rmCountry:
              $this->gsq_langs = $this->LoadLanguageSearch($this->result->text, 2, $this->result->allmatch);
              $this->gsq_count += sizeof($this->gsq_langs);
              break;
            case rmLanguage:
              $this->gsq_keyboards = $this->LoadKeyboardSearch($this->result->text, 2);
              $this->gsq_count += sizeof($this->gsq_keyboards);
              break;
            case rmKeyboard:
              $this->gsq_keyboards = $this->LoadKeyboardSearch($this->result->text, 0);
              $this->gsq_count += sizeof($this->gsq_keyboards);
              break;
          }
        } else if($this->result->legacy) {
          if($this->result->rangematch == rmKeyboard) {
            $this->gsq_keyboards = $this->LoadKeyboardSearch($this->result->text, 3);
            $this->gsq_count += sizeof($this->gsq_keyboards);
          }
        } else {
          if($this->result->rangematch & rmCountry) {
            $this->gsq_countries = $this->LoadRegionSearch($this->result->text, $this->result->regionmatch ? 2 : 1);
            $this->gsq_count += sizeof($this->gsq_countries);
          }

          if($this->result->rangematch & rmLanguage) {
            $this->gsq_langs = $this->LoadLanguageSearch($this->result->text, 1, $this->result->allmatch);
            $this->gsq_count += sizeof($this->gsq_langs);
          }

          if($this->result->rangematch & rmKeyboard) {
            $this->gsq_keyboards = $this->LoadKeyboardSearch($this->result->text, 1);
            $this->gsq_count += sizeof($this->gsq_keyboards);
          }
        }

        $this->gsq_complete = true;
      }
      $rangetext = $this->gsq_rangetext;
      $count = $this->gsq_count;
      $countries = $this->gsq_countries;
      $langs = $this->gsq_langs;
      $keyboards = $this->gsq_keyboards;
      return true;
    }

    function WriteSearchResults() {
      $this->GetSearchQueries($rangetext, $count, $countries, $langs, $keyboards);

      $keyboards = $this->FilterKeyboards($keyboards);

      $result = array();

      if(!empty($rangetext)) {
        $result['rangetext'] = $rangetext;
      }

      if(isset($countries) && sizeof($countries) > 0) {
        $result['countries'] = array();
        foreach ($countries as $country) {
          array_push($result['countries'], $country);
        }
      }

      if(isset($langs) && sizeof($langs) > 0) {
        $result['languages'] = array();
        foreach($langs as $lang) {
          array_push($result['languages'], $lang);
        }
      }

      if(isset($keyboards) && sizeof($keyboards) > 0) {
        $result['keyboards'] = array();
        foreach ($keyboards as $keyboard) {
          array_push($result['keyboards'], $keyboard);
        }
      }

      return $result;
    }

    function new_query($s): PDOStatement {
      return $this->mssql->prepare($s);
    }

    function RegexEscape($text) {
      $result = '%' . $text . '%';
      return $result;
    }

    /**
      LoadRegionSearch
    */

    function LoadRegionSearch($text, $matchtype) {
      $stmt = $this->new_query('EXEC sp_country_search_10 ?,?,?');
      // For ISO matches, we are actually searching on plain text. For all others, it's a regex so escape everything to avoid polluting the regex
      $regextext = $this->RegexEscape($text);
      $stmt->bindParam(1, $regextext);
      $stmt->bindParam(2, $text);
      $stmt->bindParam(3, $matchtype, PDO::PARAM_INT);
      $stmt->execute() || fail('Unable to execute search for sp_country_search');
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $stmt->nextRowset();
      $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $stmt->nextRowset();
      $keyboards = $stmt->fetchAll(PDO::FETCH_ASSOC);

      for($i = count($languages) - 1; $i >= 0; $i--) {
        $languages[$i]['keyboards'] = array();
        foreach($keyboards as $keyboard) {
          if($keyboard['language_id'] == $languages[$i]['id']) {
            array_push($languages[$i]['keyboards'], $keyboard['keyboard_id']);
          }
        }
        if(count($languages[$i]['keyboards']) == 0) {
          // we don't support allmatch for country searches because it's too big a dataset
          array_splice($languages, $i, 1);
        }
      }

      for($i = count($data) - 1; $i >= 0; $i--) {
        $data[$i]['languages'] = array();
        foreach($languages as $language) {
          if($language['country_id'] == $data[$i]['id']) {
            array_push($data[$i]['languages'], $language);
          }
        }
        if(count($data[$i]['languages']) == 0) {
          array_splice($data, $i, 1);
        }
      }

      return $data;
    }

    /**
      LoadLanguageSearch
    */

    function LoadLanguageSearch($text, $matchtype, $allmatch) {
      $stmt = $this->new_query('EXEC sp_language_search_10 ?,?,?,?');
      $allmatch = $allmatch ? 1 : 0;
      // For ISO matches, we are actually searching on plain text. For all others, it's a regex so escape everything to avoid polluting the regex
      $regextext = $this->RegexEscape($text);
      $stmt->bindParam(1, $regextext);
      $stmt->bindParam(2, $text);
      $stmt->bindParam(3, $matchtype);
      $stmt->bindParam(4, $allmatch);
      $stmt->execute();
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $stmt->nextRowset();
      $keyboards = $stmt->fetchAll();

      for($i = count($data) - 1; $i >= 0; $i--) {
        $data[$i]['keyboards'] = array();
        foreach($keyboards as $keyboard) {
          if($keyboard['language_id'] == $data[$i]['id']) array_push($data[$i]['keyboards'], $keyboard['keyboard_id']);
        }
        if(count($data[$i]['keyboards']) == 0) {
          if($allmatch) {
            unset($data[$i]['keyboards']);
          } else {
            array_splice($data, $i, 1);
          }
        }
      }

      return $data;
    }

    /**
      LoadKeyboardSearch
    */

    function LoadKeyboardSearch($text, $matchtype) {
      $data = [];
      $regextext = $this->RegexEscape($text);
      $stmt = $this->new_query('EXEC sp_keyboard_search_10 ?,?,?');
      $stmt->bindParam(1, $regextext);
      $stmt->bindParam(2, $text);
      $stmt->bindParam(3, $matchtype, PDO::PARAM_INT);
      $stmt->execute();
      // $data = $stmt->fetchAll();



      while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE) {
        // Append 'deprecated' to the model data
        $rowdata = json_decode($row['keyboard_info']);
        if($row['deprecated']) $rowdata->deprecated = true;
        array_push($data, $rowdata);
      }

      return $data;
    }

    /**
      FilterKeyboards: filter out keyboards that don't match platform
    */

    function FilterKeyboards($keyboards) {
      if(empty($this->platform)) {
        return $keyboards;
      }

      $p = $this->platform;

      $result = array();

      foreach($keyboards as $keyboard) {
        if(isset($keyboard->platformSupport)) {
          if(!isset($keyboard->platformSupport->$p) || $keyboard->platformSupport->$p == 'none') {
            continue;
          }
        }
        array_push($result, $keyboard);
      }
      return $result;
    }
  }
?>