<?php
  require_once(__DIR__ . '/../../tools/util.php');
  require_once(__DIR__ . '/../../tools/db/db.php');

  class KeyboardSearchResult {
    const CONTEXT_KEYBOARD='keyboard';
    const CONTEXT_LANGUAGE='language';
    const CONTEXT_REGION='region';

    public $context;  // keyboard|language|region
    public $idmatch;  // if true, searching for an id
    public $allmatch; // ? not sure if needed
    public $text, $searchtext;

    public $pageNumber, $pageSize, $totalRows;
    public $platform;

    public $keyboards, $languages, $regions; // Only one of these can be populated at a time
  }

  class KeyboardSearch {
    private $mssql;

    const PAGESIZE = 10;

    function __construct($mssql) {
      $this->mssql = $mssql;
    }

    function GetSearchMatches($context, $platform, $query, $pageNumber) {
      $result = new KeyboardSearchResult();
      $result->context = $context;
      $result->pageSize = KeyboardSearch::PAGESIZE;
      $result->pageNumber = $pageNumber;

      if(in_array($platform, array('macos', 'windows', 'linux', 'android', 'ios'))) {
        $result->platform = $platform;
      } else if (in_array($platform, ['desktopWeb', 'mobileWeb'])) {
        $result->platform = 'web';
      }

      $result->idmatch = false;
      $result->allmatch = false;
      $result->regionmatch = false;
      $result->legacy = false;

      $textparts = explode(':', $query);

      for($i = 0; $i < sizeof($textparts) - 1; $i++) {
        $match = strtolower($textparts[$i]);
        if(!strcmp($match, 'id')) $result->idmatch = true;
        else if(!strcmp($match, 'all')) $result->allmatch = true;
        else if(!strcmp($match, 'region')) $result->regionmatch = true;
        else if(!strcmp($match, 'legacy')) $result->legacy = true;
      }

      $result->text = array_pop($textparts);
      $result->searchtext = $result->text;

      return $this->WriteSearchResults($result);
    }

    private function GetSearchQueries(KeyboardSearchResult $result) {//}, &$rangetext, &$count, &$countries, &$langs, &$keyboards) {
      $result->searchtext = strip_tags($result->searchtext);

      switch($result->context) {
      case KeyboardSearchResult::CONTEXT_KEYBOARD:
        if($result->idmatch) {
          $result->rangetext = "Keyboard with id '{$result->searchtext}'";
          $result->keyboards = $this->LoadKeyboardSearch($result->text, $result, 2);
        } else if($result->legacy) {
          $result->rangetext = "Keyboard with legacy id '{$result->searchtext}'";
          $result->keyboards = $this->LoadKeyboardSearch($result->text, $result, 3);
        } else {
          $result->rangetext = "Keyboards matching '{$result->searchtext}'";
          $result->keyboards = $this->LoadKeyboardSearch($result->text, $result, 1);
        }
        // $rangetext = "Keyboards for language with BCP 47 code '{$result->searchtext}'";
        //        $keyboards = $this->LoadKeyboardSearch($result->text, 0);
        break;

      case KeyboardSearchResult::CONTEXT_LANGUAGE:
        if($result->allmatch) {
          $result->rangetext = "All languages matching '{$result->searchtext}'";
          $result->languages = $this->LoadLanguageSearch($result->text, 1, $result->allmatch);
        } else if($result->idmatch) {
          $result->rangetext = "Languages with BCP 47 code '{$result->searchtext}'";
          $result->languages = $this->LoadLanguageSearch($result->text, 1, $result->allmatch);
        } else {
          $result->rangetext = "Languages matching '{$result->searchtext}'";
          $result->languages = $this->LoadLanguageSearch($result->text, 0, $result->allmatch); //?
        }
        break;

      case KeyboardSearchResult::CONTEXT_REGION:
        if($result->regionmatch) {
          $result->rangetext = "Countries in {$result->region}";
          $result->countries = $this->LoadRegionSearch($result->text, $result->regionmatch ? 2 : 1);
        }
        //else if($result->allmatch && $result->idmatch) $rangetext = "All languages for country with ISO3166-1 code '{$result->searchtext}'";
        //else if($result->allmatch) $rangetext = "Countries matching '{$result->searchtext}'";
        else if($result->idmatch) {
//          $rangetext = "Languages for country with ISO3166-1 code '{$result->searchtext}'";
//          $langs = $this->LoadLanguageSearch($result->text, 2, $result->allmatch);
          $result->countries = $this->LoadRegionSearch($result->text, $result->regionmatch ? 2 : 1);
        } else {
          $result->rangetext = "Countries matching '{$result->searchtext}'";
          $result->countries = $this->LoadRegionSearch($result->text, $result->regionmatch ? 2 : 1);
        }
        break;
      }

      return true;
    }

    private function WriteSearchResults(KeyboardSearchResult $result) {
      $this->GetSearchQueries($result);

      $data = array();

      $totalPages = round(($result->totalRows + $result->pageSize - 1)/$result->pageSize);

      // TODO: fixup schema

      $data['context'] = [
        'range' => $result->rangetext,
        'context' => $result->context,
        'platform' => $result->platform,
        'pageSize' => $result->pageSize,
        'pageNumber' => $result->pageNumber,
        'totalRows' => $result->totalRows,
        'totalPages' => $totalPages
      ];

      // TODO: include weighting, matched term, popularity values

      if(isset($result->countries) && sizeof($result->countries) > 0) {
        $data['countries'] = array();
        foreach ($result->countries as $country) {
          array_push($data['countries'], $country);
        }
      }

      if(isset($result->languages) && sizeof($result->languages) > 0) {
        $data['languages'] = array();
        foreach($result->languages as $lang) {
          array_push($data['languages'], $lang);
        }
      }

      if(isset($result->keyboards) && sizeof($result->keyboards) > 0) {
        $data['keyboards'] = array();
        foreach ($result->keyboards as $keyboard) {
          array_push($data['keyboards'], $keyboard);
        }
      }

      return $data;
    }

    function new_query($s) {
      return $this->mssql->prepare($s);
    }

    function RegexEscape($text) {
      return $text . '%';
    }

    function CleanQueryString($text) {
      // strip out characters we can't use in full text search
      return preg_replace("/[^a-zA-Z0-9']/", '', $text);
    }

    /**
     * LoadRegionSearch
     */

    function LoadRegionSearch($text, $matchtype) {
      // TODO: pagination, langtags-based search
      $stmt = $this->new_query('EXEC sp_country_search ?,?,?');
      // For ISO matches, we are actually searching on plain text. For all others, it's a regex so escape everything to avoid polluting the regex
      $regextext = $this->RegexEscape($text);
      $stmt->bindParam(1, $regextext);
      $stmt->bindParam(2, $text);
      $stmt->bindParam(3, $matchtype, PDO::PARAM_INT);
      $stmt->execute();
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $stmt->nextRowset();
      $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $stmt->nextRowset();
      $keyboards = $stmt->fetchAll();

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
     *  LoadLanguageSearch
     */

    function LoadLanguageSearch($text, $matchtype, $allmatch) {
      // TODO: pagination, langtags-based search
      $stmt = $this->new_query('EXEC sp_language_search ?,?,?,?');
      $allmatch = $allmatch ? 1 : 0;
      // For ISO matches, we are actually searching on plain text. For all others, it's a regex so escape everything to avoid polluting the regex
      $regextext = $this->RegexEscape($text);
      //var_dump($matchtype, $text); exit;
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
     * LoadKeyboardSearch
     */

    function LoadKeyboardSearch($text, KeyboardSearchResult $result, $matchtype) {
      $data = [];

      switch($matchtype) {
      case 1: // generic text search
        $text = $this->CleanQueryString($text);
        $stmt = $this->new_query('EXEC sp_keyboard_search ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $result->platform);
        $stmt->bindParam(3, $result->pageNumber);
        $stmt->bindParam(4, $result->pageSize);
        break;

      case 0: // match on keyboard id
      case 2: // match on language tag
      case 3: // match on legacy id
        $stmt = $this->new_query('EXEC sp_keyboard_search_alt ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $matchtype);
        $stmt->bindParam(3, $result->pageNumber);
        $stmt->bindParam(4, $result->pageSize);
        break;
      }

      $stmt->execute();

      $result->totalRows = $stmt->fetchAll()[0]['total_count'];

      $stmt->nextRowset();

      $result = [];

      $data = $stmt->fetchAll();
      for($i = 0; $i < count($data); $i++) {
        $row = $data[$i];
        $rowdata = json_decode($row['keyboard_info']);
        if($row['deprecated']) $rowdata->deprecated = true;
        array_push($result, $rowdata);
      }

      return $result;
    }
  }
