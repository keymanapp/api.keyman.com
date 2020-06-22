<?php
  require_once(__DIR__ . '/../../../tools/util.php');
  require_once(__DIR__ . '/../../../tools/db/db.php');

  class KeyboardSearchResult {
    const FILTER_SEARCH='search'; // default, search for the text string
    const FILTER_ALL='all';       // return all possible results ?? TODO: check meaning of this
    const FILTER_ID='id';         // filter by identifier e.g. keyboard id, language tag, etc.
    const FILTER_LEGACY='legacy'; // keyboards only, filter by a legacy id
    const FILTER_LANGUAGE='language'; // search string is a language tag
    const FILTER_REGION='region';     // search string is a country identifier

    public string $filter;   // none|id|legacy|language|region

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

    function GetSearchMatches($platform, $query, $pageNumber) {
      $result = new KeyboardSearchResult();
      $result->pageSize = KeyboardSearch::PAGESIZE;
      $result->pageNumber = $pageNumber;

      if(in_array($platform, array('macos', 'windows', 'linux', 'android', 'ios'))) {
        $result->platform = $platform;
      } else if (in_array($platform, ['desktopWeb', 'mobileWeb', 'web'])) {
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
      return $this->LoadKeyboardSearch($result);
    }

    private function WriteSearchResults(KeyboardSearchResult $result) {
      if(!$this->GetSearchQueries($result))
        return null;

      $data = array();

      $totalPages = round(($result->totalRows + $result->pageSize - 1)/$result->pageSize);

      // TODO: fixup schema

      $data['context'] = [
        'range' => $result->rangetext,
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
      if(preg_match_all("/(\\p{L}|[ _])/u", $text, $matches)) {
        $r = implode('', $matches[0]);
      } else {
        $r = "";
      }
      return $r;
    }

    function QueryStringToIdSearch($text) {
      return preg_replace("/[^a-z0-9_. ]/i", '', $text);
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
        $idtext = $this->QueryStringToIdSearch($text);
        $stmt = $this->new_query('EXEC sp_keyboard_search ?, ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $idtext);
        $stmt->bindParam(3, $result->platform);
        $stmt->bindParam(4, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(5, $result->pageSize, PDO::PARAM_INT);
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
        case 5: return 'keyboard_id';
        case 6: return 'language_id';
        //case 7: return 'region_id';
        default: return 'unknown';
       }
    }
  }
