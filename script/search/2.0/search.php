<?php
  /**
   * https://api.keyman.com/search/2.0?[f=1&]q=query-string
   *
   * Search for a keyboard. Returns a result that lists all keyboards that match.
   * https://api.keyman.com/schemas/search/2.0/search.json is JSON schema
   *
   * @param q    query-string   a partial string to search for in keyboard name, id, description, language, script, country.
   *                            name, description, language, script, country all use full text search with decomposition of
   *                            diacritics, normalisation etc. Ids use plain text matches.
   *                            prefixes:  c:<name>    show keyboards for the countries matching <name>
   *                                       c:id:<id>   show keyboards for the country with ISO 3166 code <id>
   *                                       l:<name>    show keyboards for the languages matching <name>
   *                                       l:id:<id>   show keyboards for the language with BCP 47 tag <id>.
   *                                                   This tag will be canonicalized according to langtags.json.
   *                                       s:<name>    show keyboards for the scripts matching <name>
   *                                       s:id:<id>   show keyboards for the scripts matching ISO 15924 code <id>
   *                                       id:<id>     show keyboards matching the id <id>
   *                                       k:id:<id>   show keyboards matching the id <id>
   *                                       legacy:<id> show keyboard with the legacy integer id <id>
   *                                       k:legacy:<id> show keyboard with the legacy integer id <id>
   * @param f                   if 1, then return formatted JSON
   * @param p                   page number to return (10 results per page) [TODO]
   * @param platform            one of 'macos', 'windows', 'linux', 'android', 'ios', 'desktopWeb', 'mobileWeb'
   */

  require_once(__DIR__ . '/../../../tools/util.php');
  require_once __DIR__ . '/../../../tools/autoload.php';
  use Keyman\Site\Common\KeymanHosts;

  allow_cors();
  json_response();

  require_once(__DIR__ . '/search.inc.php');
  require_once(__DIR__ . '/../../../tools/db/db.php');
  $mssql = Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

  if(!isset($_REQUEST['q'])) {
    fail('Query string must be set');
  }

  header('Link: <' . KeymanHosts::Instance()->api_keyman_com . '/schemas/search/2.0/search.json#>; rel="describedby"');
  //header('') TODO: add page information to results

  $query = $_REQUEST['q'];
  $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : null;
  $obsolete = !empty($_REQUEST['obsolete']);

  if(isset($_REQUEST['p'])) {
    $pageNumber = (int)($_REQUEST['p']);
    if($pageNumber < 1 || $pageNumber > 999) $pageNumber = 1;
  } else {
    $pageNumber = 1;
  }

  $s = new KeyboardSearch($mssql);
  $json = $s->GetSearchMatches($platform, $query, $obsolete, $pageNumber);

  if(isset($_REQUEST['f']) && !empty($_REQUEST['f']))
    json_print($json);
  else
    echo json_encode($json, JSON_UNESCAPED_SLASHES);
