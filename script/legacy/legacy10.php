<?php
  // Mimic the KeymanWeb Cloud json 1.0 API but from our t_keyboards data

  require_once('../../tools/util.php');
  require_once('legacy_db.php');
  require_once('legacy_utils.php');
  require_once __DIR__ . '/../../tools/autoload.php';

  use Keyman\Site\Common\KeymanHosts;

  allow_cors();
  json_response();

  header('Link: <' . KeymanHosts::Instance()->api_keyman_com . '/schemas/keymanweb-cloud-api/keymanweb-cloud-api.1.0.json#>; rel="describedby"');

  if(isset($_REQUEST['context'])) {
    $context = $_REQUEST['context'];
  } else {
    $context = 'language';
  }

  validateVersion(isset($_REQUEST['version']) ? $_REQUEST['version'] : '');

  if(isset($_REQUEST['keyboardid'])) {
    $keyboardid = $_REQUEST['keyboardid'];
  } else {
    $keyboardid = '';
  }
  if(isset($_REQUEST['languageid'])) {
    $languageid = translate6393ToBCP47($_REQUEST['languageid']);
  } else {
    $languageid = '';
  }

  $isMobileDevice = isset($_REQUEST['device']) && ($_REQUEST['device'] == 'ipad' || $_REQUEST['device'] == 'iphone');

  $dateFormatSeconds = isset($_REQUEST['dateformat']) && $_REQUEST['dateformat'] == 'seconds';

  if($context == 'language') {
    $response = getLanguages($languageid);
  } else if($context == 'keyboard') {
    $response = getKeyboards($keyboardid, $languageid);
  } else {
    fail('Invalid function', 400);
  }

  echo json_encode($response);


  function isKeyboardFiltered($keyboard_id) {
    global $isMobileDevice;

    return $isMobileDevice && (
      $keyboard_id == 'european' ||
      $keyboard_id == 'chinese' ||
      $keyboard_id == 'japanese' ||
      $keyboard_id == 'korean_rr');
  }

  function getLanguages($id) {
    $languages = DB_LoadLanguages($id);

    $LastID = '';
    $res = array();
    foreach($languages as $language) {
      if(isKeyboardFiltered($language['keyboard_id'])) {
        continue;
      }

      $langid = translateLanguageIdToOutputFormat($language['bcp47']);
      if($LastID != $langid) {
        if(isset($reslang) && !empty($reslang)) {
          $reslang['keyboards'] = $reskbds;
          array_push($res, $reslang);
        }
        $reslang = array('name' => $language['language_name'], 'id' => $langid);
        $reskbds = array();
        $LastID = $langid;
      }

      $reskbd = array(
        'id' => $language['keyboard_id'],
        'name' => $language['name'],
        'uri' => getKeyboardURI($language['keyboard_id'], $language['version']),
        'lastModified' => dateFormat($language['last_modified']),
        'fileSize' => $language['js_filesize']);

      if($language['is_rtl']) {
        $reskbd['rtl'] = true;
      }

      //if($language->DefaultForLanguage)
      //  $reskbd['default'] = true;

      array_push($reskbds, $reskbd);
    }

    if(isset($reslang) && !empty($reslang)) {
      $reslang['keyboards'] = $reskbds;
      array_push($res, $reslang);
    }

    if(empty($id)) {
      return array('languages' => $res);
    }

    if(count($res) == 1) {
      return $res[0];
    }

    return $res;
  }

  function getKeyboardInfo($keyboard, $includeVersion) {
    $jskeyboard = array(
      'id' => $keyboard['keyboard_id'],
      'uri' => getKeyboardURI($keyboard['keyboard_id'], $keyboard['version']),
      'lastModified' => dateFormat($keyboard['last_modified'])
    );
    if($keyboard['is_rtl']) {
      $jskeyboard['rtl'] = true;
    }
    if($includeVersion) {
      $jskeyboard['version'] = $keyboard['version'];
    }

    // Load languages
    $jslanguages = array();
    $languages = DB_LoadKeyboardLanguages($keyboard['keyboard_id']);
    foreach($languages as $language) {
      array_push($jslanguages, array(
        'id' => translateLanguageIdToOutputFormat($language['bcp47']),
        'name' => $language['name']
      ));
    }
    $jskeyboard['languages'] = $jslanguages;
    return $jskeyboard;
  }

  function getKeyboards($keyboardid, $languageid) {
    if(empty($languageid) && empty($keyboardid)) {
      $keyboards = DB_LoadKeyboards(null);
    } else if(empty($languageid)) {
      $keyboards = DB_LoadKeyboards($keyboardid);
      if(sizeof($keyboards) > 0) {
        return getKeyboardInfo($keyboards[0], false);
      }
      fail('Keyboard not found', 404);
    } else if(empty($keyboardid)) {
      $keyboards = DB_LoadKeyboardsForLanguage($languageid);
    } else {
      fail("Not implemented", 501);
    }

    $jskeyboards = array();
    foreach($keyboards as $keyboard) {
      // note - following line temp until we have KMW2.0 with device-specific exclusions
      if(isKeyboardFiltered($keyboard['keyboard_id'])) {
        continue;
      }
      $jskeyboard = getKeyboardInfo($keyboard, false);
      array_push($jskeyboards, $jskeyboard);
    }

    return array('keyboards' => $jskeyboards);
  }

  function getKeyboardURI($name, $version) {
    global $KeymanWebSiteName, $SERVER_PROTOCOL;
    return KeymanHosts::Instance()->s_keyman_com ."/keyboard/$name/$version/$name-$version.js";
  }

?>
