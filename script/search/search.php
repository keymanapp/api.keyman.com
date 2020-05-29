<?php
  /**
   * https://api.keyman.com/search?q=query-string
   *
   * Search for a keyboard. Returns a result that lists all keyboards, languages and countries that match.
   * https://api.keyman.com/schemas/search.json is JSON schema
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

  require_once(__DIR__ . '/../../tools/util.php');

  allow_cors();
  json_response();

  header('Link: <https://api.keyman.com/schemas/search.json#>; rel="describedby"');

  require_once(__DIR__ . '/search.inc.php');

  if(!isset($_REQUEST['q'])) {
    fail('Query string must be set');
  }

  $q = $_REQUEST['q'];
  $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : null;

  $s = new KeyboardSearch();
  if(!empty($platform)) {
    $s->SetPlatform($platform);
  }
  $s->GetSearchMatches($q);
  $json = $s->WriteSearchResults();

  json_print($json);
