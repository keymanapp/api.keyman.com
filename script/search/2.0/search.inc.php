<?php
  require_once(__DIR__ . '/../../../tools/util.php');
  require_once(__DIR__ . '/../../../tools/db/db.php');

  class KeyboardSearchResult {
    const FILTER_SEARCH='search'; // default, search for the text string [any context]
    const FILTER_ALL='all';       // return all possible results ?? TODO: check meaning of this
    const FILTER_ID='id';         // filter by identifier per context, e.g. keyboard id, language tag, etc. [any context]
    const FILTER_LEGACY='legacy'; // keyboards only, filter by a legacy id [CONTEXT_KEYBOARD]
    const FILTER_LANGUAGE='language'; // search string is a language tag; [CONTEXT_KEYBOARD]
    const FILTER_REGION='region';     // search string is a country identifier; [CONTEXT_LANGUAGE, later:CONTEXT_KEYBOARD]

    const CONTEXT_KEYBOARD='keyboard';
    const CONTEXT_LANGUAGE='language';
    const CONTEXT_REGION='region';

    public string $filter;   // none|id|legacy|language|region
    public string $context;  // keyboard|language|region

    public $text, $searchtext;

    public int $pageNumber, $pageSize, $totalRows;
    public $platform;

    public $keyboards, $languages, $regions; // Only one of these can be populated at a time
  }

  class KeyboardSearch {
    private $mssql;

    const PAGESIZE = 100; //TODO: reduce to 10 once we support pagniation on keyman.com

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

      $result->filter = KeyboardSearchResult::FILTER_SEARCH;
      $result->regionmatch = false;

      $textparts = explode(':', $query);

      for($i = 0; $i < sizeof($textparts) - 1; $i++) {
        $match = strtolower($textparts[$i]);
        if(!strcmp($match, 'bcp47')) $result->filter = KeyboardSearchResult::FILTER_LANGUAGE;
        else if(!strcmp($match, 'id')) $result->filter = KeyboardSearchResult::FILTER_ID;
        else if(!strcmp($match, 'all')) $result->allmatch = true;
        else if(!strcmp($match, 'region')) $result->regionmatch = true;
        else if(!strcmp($match, 'legacy')) $result->filter = KeyboardSearchResult::FILTER_LEGACY;
      }

      $result->text = array_pop($textparts);
      $result->searchtext = $result->text;

      return $this->WriteSearchResults($result);
    }

    private function GetSearchQueries(KeyboardSearchResult $result) {
      $result->searchtext = strip_tags($result->searchtext);

      switch($result->context) {
      case KeyboardSearchResult::CONTEXT_KEYBOARD:
        return $this->LoadKeyboardSearch($result);

      case KeyboardSearchResult::CONTEXT_LANGUAGE:
        switch($result->filter) {
        case KeyboardSearchResult::FILTER_ALL:
          $result->rangetext = "All languages matching '{$result->searchtext}'";
          $result->languages = $this->LoadLanguageSearch($result->text, 1, $result->allmatch);
          break;
        case KeyboardSearchResult::FILTER_ID:
          $result->rangetext = "Languages with BCP 47 code '{$result->searchtext}'";
          $result->languages = $this->LoadLanguageSearch($result->text, 1, $result->allmatch);
          break;
        case KeyboardSearchResult::FILTER_SEARCH:
          $result->rangetext = "Languages matching '{$result->searchtext}'";
          $result->languages = $this->LoadLanguageSearch($result->text, 0, $result->allmatch); //?
          break;
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
        'pageSize' => $result->pageSize,
        'pageNumber' => $result->pageNumber,
        'totalRows' => $result->totalRows,
        'totalPages' => $totalPages
      ];

      if($result->platform !== null)
        $data['context']['platform'] = $result->platform;

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

    function LoadKeyboardSearch(KeyboardSearchResult $result) {
      $text = $result->text;
      switch($result->filter) {
      case KeyboardSearchResult::FILTER_LANGUAGE:
        $result->rangetext = "Keyboards for language with BCP 47 code '{$result->searchtext}'";
        // match on language tag
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_language_tag ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $result->platform);
        $stmt->bindParam(3, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(4, $result->pageSize, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_ID:
        // match on keyboard id
        // We ignore platform. Only one row
        $result->rangetext = "Keyboard with id '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_id ?');
        $stmt->bindParam(1, $text);
        break;

      case KeyboardSearchResult::FILTER_LEGACY:
        // match on legacy id
        // We ignore platform. Only one row
        $result->rangetext = "Keyboard with legacy id '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_legacy_id ?');
        $legacy_id = (int) $text;
        $stmt->bindParam(1, $legacy_id, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_SEARCH:
        // generic text search
        $result->rangetext = "Keyboards matching '{$result->searchtext}'";
        $text = $this->CleanQueryString($text);
        $stmt = $this->new_query('EXEC sp_keyboard_search ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $result->platform);
        $stmt->bindParam(3, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(4, $result->pageSize, PDO::PARAM_INT);
        break;

      default:
        return false;
      }

      $stmt->execute();
      $data = $stmt->fetchAll();

      // if the result set has a total_count field and just one row, it's a summary set for paginated results
      if(count($data) == 1 && isset($data[0]['total_count'])) {
        $result->totalRows = $data[0]['total_count'];
        if(isset($data[0]['base_tag'])) {
          // Special case: we normalise the bcp 47 tag when we pass it in.
          $result->rangetext = "Keyboards for language with BCP 47 code '{$data[0]['base_tag']}'";
        }
        $stmt->nextRowset();
        $data = $stmt->fetchAll();
      } else {
        $result->totalRows = count($data);
      }

      $result->keyboards = [];

      for($i = 0; $i < count($data); $i++) {
        $row = $data[$i];
        $rowdata = json_decode($row['keyboard_info']);
        if($row['deprecated']) $rowdata->deprecated = true;

        $rowdata->match = [
          'name' => $row['match_name'],
          'type' => $this->match_type_name($row['match_type']),
          'weight' => $row['match_weight'],
          'downloads' => $row['download_count'],
          'final_weight' => $row['final_weight']
        ];

        array_push($result->keyboards, $rowdata);
      }

      return $result;
    }

    function match_type_name($match_type) {
      switch($match_type) {
        case 0: return 'keyboard';
        case 1: return 'description';
        case 2: return 'language';
        case 3: return 'script';
        case 4: return 'region';
        default: return 'unknown';
       }
    }
  }
