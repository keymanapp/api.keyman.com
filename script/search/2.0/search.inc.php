<?php
  require_once(__DIR__ . '/../../../tools/util.php');
  require_once(__DIR__ . '/../../../tools/db/db.php');

  class KeyboardSearchResult {
    const FILTER_DEFAULT='default';            // FILTER_KEYBOARD|FILTER_KEYBOARD_ID|FILTER_LANGUAGE|FILTER_COUNTRY|FILTER_SCRIPT
    const FILTER_POPULARITY='popularity';      // List keyboards by popularity (excludes deprecated and non-Unicode)
    const FILTER_KEYBOARD='keyboard';          // Filter by keyboard name, decription or id only
    const FILTER_KEYBOARD_ID='keyboard_id';    // Filter by keyboard id initial substring match
    const FILTER_LEGACY='legacy';              // Filter by a legacy (integer) keyboard id
    const FILTER_LANGUAGE='language';          // Filter by language name (any matching name)
    const FILTER_LANGUAGE_ID='language_id';    // Filter by language ID, initial substring match (caveat: kh doesn't match khm, but does match kh-Khmr)
    const FILTER_COUNTRY='country';            // Filter by country name (any matching name)
    const FILTER_COUNTRY_ID='country_id';      // Filter by country ID, exact match (only two letters anyway)
    const FILTER_SCRIPT='script';              // Filter by script name (any matching name)
    const FILTER_SCRIPT_ID='script_id';        // Filter by script ID, initial substring match

    public string $filter;   // any of the FILTER options above
    public bool $obsolete;

    public $text, $searchtext;

    public int $pageNumber, $pageSize, $totalRows;
    public $platform;

    public $keyboards;
  }

  class KeyboardSearch {
    private $mssql;

    const PAGESIZE = 10;

    const FILTERS = [
      'k:id:'      => KeyboardSearchResult::FILTER_KEYBOARD_ID,
      'k:legacy:'  => KeyboardSearchResult::FILTER_LEGACY,
      'k:'         => KeyboardSearchResult::FILTER_KEYBOARD,
      'id:'        => KeyboardSearchResult::FILTER_KEYBOARD_ID,
      'legacy:'    => KeyboardSearchResult::FILTER_LEGACY,
      'l:id:'      => KeyboardSearchResult::FILTER_LANGUAGE_ID,
      'l:'         => KeyboardSearchResult::FILTER_LANGUAGE,
      'c:id:'      => KeyboardSearchResult::FILTER_COUNTRY_ID,
      'c:'         => KeyboardSearchResult::FILTER_COUNTRY,
      's:id:'      => KeyboardSearchResult::FILTER_SCRIPT_ID,
      's:'         => KeyboardSearchResult::FILTER_SCRIPT,
      'p:'         => KeyboardSearchResult::FILTER_POPULARITY
    ];

    function __construct($mssql) {
      $this->mssql = $mssql;
    }

    function GetSearchMatches($platform, $query, $obsolete, $pageNumber) {
      $result = new KeyboardSearchResult();
      $result->pageSize = KeyboardSearch::PAGESIZE;
      $result->pageNumber = $pageNumber;
      $result->obsolete = $obsolete;

      if(in_array($platform, array('macos', 'windows', 'linux', 'android', 'ios'))) {
        $result->platform = $platform;
      } else if (in_array($platform, ['desktopWeb', 'mobileWeb', 'web'])) {
        $result->platform = 'web';
      }

      $query = trim($query);

      if(preg_match('/^('.implode('|',array_keys(KeyboardSearch::FILTERS)).')(.+)$/', $query, $matches)) {
        $result->text = $matches[2];
        $result->filter = KeyboardSearch::FILTERS[$matches[1]];
      } else {
        $result->text = $query;
        $result->filter = KeyboardSearchResult::FILTER_DEFAULT;
      }

      $result->searchtext = strip_tags($result->text);

      return $this->WriteSearchResults($result);
    }

    private function WriteSearchResults(KeyboardSearchResult $result) {
      $data = ['keyboards' => []];

      $status = $this->GetSearchQueries($result);

      $totalPages = intval(($result->totalRows + $result->pageSize - 1)/$result->pageSize);

      $data['context'] = [
        'range' => $result->rangetext,
        'text' => $result->searchtext,
        'pageSize' => $result->pageSize,
        'pageNumber' => $result->pageNumber,
        'totalRows' => $result->totalRows,
        'totalPages' => $totalPages
      ];

      if(!$status) {
        return $data;
      }

      if($result->platform !== null) {
        $data['context']['platform'] = $result->platform;
      }

      if(isset($result->keyboards) && sizeof($result->keyboards) > 0) {
        foreach ($result->keyboards as $keyboard) {
          array_push($data['keyboards'], $keyboard);
        }
      }

      return $data;
    }

    function new_query($s): PDOStatement {
      return $this->mssql->prepare($s);
    }

    function RegexEscape($text) {
      return $text . '%';
    }

    function CleanQueryString($text) {
      // strip out characters we can't use in full text search
      if(preg_match_all("/(\\p{L}|\\p{M}|\\p{N}|[ _0-9-'])/u", $text, $matches)) {
        $r = implode('', $matches[0]);
      } else {
        $r = "";
      }
      return $r;
    }

    function QueryStringToIdSearch($text) {
      return preg_replace("/[^a-z0-9_. -]/i", '', $text);
    }

    /**
     * GetSearchQueries
     */

    private function GetSearchQueries(KeyboardSearchResult $result) {
      $result->totalRows = 0;
      $text = $this->CleanQueryString($result->text);
      $idtext = $this->QueryStringToIdSearch($text);

      switch($result->filter) {
      case KeyboardSearchResult::FILTER_DEFAULT:
        // generic text search
        $result->rangetext = "Keyboards matching '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search ?, ?, ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $idtext);
        $stmt->bindParam(3, $result->platform);
        $stmt->bindParam(4, $result->obsolete);
        $stmt->bindParam(5, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(6, $result->pageSize, PDO::PARAM_INT);
        break;
      case KeyboardSearchResult::FILTER_POPULARITY:
        // list most popular keyboards (skip obsolete always)
        $result->rangetext = "Popular keyboards";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_popularity ?, ?, ?');
        $stmt->bindParam(1, $result->platform);
        $stmt->bindParam(2, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(3, $result->pageSize, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_KEYBOARD:
        $result->rangetext = "Keyboards matching '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_keyboard ?, ?, ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $idtext);
        $stmt->bindParam(3, $result->platform);
        $stmt->bindParam(4, $result->obsolete);
        $stmt->bindParam(5, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(6, $result->pageSize, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_KEYBOARD_ID:
        // match on keyboard id
        // We ignore platform
        $result->rangetext = "Keyboard with id '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_id ?, ?');
        $stmt->bindParam(1, $idtext);
        $stmt->bindParam(2, $result->obsolete);
        break;

      case KeyboardSearchResult::FILTER_LEGACY:
        // match on legacy id
        // We ignore platform and obsolete status. Only one row
        $result->rangetext = "Keyboard with legacy id '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_legacy_id ?');
        $legacy_id = intval($idtext);
        $stmt->bindParam(1, $legacy_id, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_LANGUAGE:
        $result->rangetext = "Keyboards for languages matching '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_language ?, ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $result->platform);
        $stmt->bindParam(3, $result->obsolete);
        $stmt->bindParam(4, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(5, $result->pageSize, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_LANGUAGE_ID:
        $result->rangetext = "Keyboards for language with BCP 47 tag '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_language_bcp47_tag ?, ?, ?, ?, ?');
        $stmt->bindParam(1, $idtext);
        $stmt->bindParam(2, $result->platform);
        $stmt->bindParam(3, $result->obsolete);
        $stmt->bindParam(4, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(5, $result->pageSize, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_COUNTRY:
        $result->rangetext = "Keyboards for countries matching '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_country ?, ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $result->platform);
        $stmt->bindParam(3, $result->obsolete);
        $stmt->bindParam(4, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(5, $result->pageSize, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_COUNTRY_ID:
        $result->rangetext = "Keyboards for country with ISO 3166 code '{$result->searchtext}'";
        // match on language tag
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_country_iso3166_code ?, ?, ?, ?, ?');
        $stmt->bindParam(1, $idtext);
        $stmt->bindParam(2, $result->platform);
        $stmt->bindParam(3, $result->obsolete);
        $stmt->bindParam(4, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(5, $result->pageSize, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_SCRIPT:
        $result->rangetext = "Keyboards for scripts matching '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_script ?, ?, ?, ?, ?');
        $stmt->bindParam(1, $text);
        $stmt->bindParam(2, $result->platform);
        $stmt->bindParam(3, $result->obsolete);
        $stmt->bindParam(4, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(5, $result->pageSize, PDO::PARAM_INT);
        break;

      case KeyboardSearchResult::FILTER_SCRIPT_ID:
        $result->rangetext = "Keyboards for script with ISO 15924 code '{$result->searchtext}'";
        $stmt = $this->new_query('EXEC sp_keyboard_search_by_script_iso15924_code ?, ?, ?, ?, ?');
        $stmt->bindParam(1, $idtext);
        $stmt->bindParam(2, $result->platform);
        $stmt->bindParam(3, $result->obsolete);
        $stmt->bindParam(4, $result->pageNumber, PDO::PARAM_INT);
        $stmt->bindParam(5, $result->pageSize, PDO::PARAM_INT);
        break;

      default:
        return false;
      }

      if(!$stmt->execute()) {
        $result->totalRows = 0;
        return false;
      }

      try {
        $data = $stmt->fetchAll();
      } catch(PDOException $e) {
        //@error_log($e->getMessage());
        return false;
      }

      // if the result set has a total_count field and just one row, it's a summary set for paginated results
      if(count($data) == 1 && isset($data[0]['total_count'])) {
        $result->totalRows = $data[0]['total_count'];
        if(isset($data[0]['base_tag'])) {
          // Special case: we normalise the bcp 47 tag when we pass it in.
          $result->rangetext = "Keyboards for language with BCP 47 tag '{$data[0]['base_tag']}'";
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
          'type' => $row['match_type'],
          'weight' => floatval($row['match_weight']),
          'downloads' => intval($row['download_count']),
          'finalWeight' => floatval($row['final_weight'])
        ];

        // TODO: when searching for country or script, then we get a fairly 'random' first match
        //       Is there any way we can improve this?
        if(!empty($row['match_tag'])) $rowdata->match['tag'] = $row['match_tag'];

        array_push($result->keyboards, $rowdata);
      }

      return $result;
    }
  }
