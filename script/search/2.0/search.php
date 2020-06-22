<?php
  /**
   * https://api.keyman.com/search/2.0?q=query-string
   *
   * Search for a keyboard. Returns a result that lists all keyboards that match.
   * https://api.keyman.com/schemas/search/2.0/search.json is JSON schema
   *
   * @param q    query-string   a partial string to search for in keyboard name, id, description, language.
   *                            prefixes:  c:id:<id>   show languages for the country with ISO code <id>
   *                                       l:id:<id>   show keyboards for the language with BCP 47 code <id>
   *                                       k:id:<id>   show keyboard with the id <id>
   *                                       c:<text>    show only countries (regions)
   *                                       l:<text>    show only languages matching <text>
   *                                       k:<text>    show only keyboards
   *                                       k:legacy:<id> show keyboard with the legacy integer id <id>
   * @param platform            one of 'macos', 'windows', 'linux', 'android', 'ios', 'desktopWeb', 'mobileWeb'
   */

  require_once(__DIR__ . '/../../../tools/util.php');

  allow_cors();
  json_response();

  require_once(__DIR__ . '/search.inc.php');
  require_once(__DIR__ . '/../../../tools/db/db.php');
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  if(!isset($_REQUEST['q'])) {
    fail('Query string must be set');
  }

  header('Link: <https://api.keyman.com/schemas/search.json#>; rel="describedby"');
  //header('') TODO: add page information to results

  $query = $_REQUEST['q'];
  $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : null;

  if(isset($_REQUEST['c'])) {
    $context = $_REQUEST['c'];
   } else {
    $context = KeyboardSearchResult::CONTEXT_KEYBOARD;
   }

  if(isset($_REQUEST['p'])) {
    $pageNumber = (int)($_REQUEST['p']);
    if($pageNumber < 1 || $pageNumber > 999) $pageNumber = 1;
  } else {
    $pageNumber = 1;
  }

  $s = new KeyboardSearch($mssql);
  $json = $s->GetSearchMatches($context, $platform, $query, $pageNumber);

  if(isset($_REQUEST['f']))
    json_print($json);
  else
    echo json_encode($json, JSON_UNESCAPED_SLASHES);
