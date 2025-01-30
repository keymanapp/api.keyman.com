<?php
  // Mimic the KeymanWeb Cloud json 4.0 API but from our t_keyboards data

  require_once('../../tools/util.php');
  require_once('legacy_db.php');
  require_once('legacy_fontutils.php');
  require_once('legacy_utils.php');
  require_once __DIR__ . '/../../tools/autoload.php';

  use Keyman\Site\Common\KeymanHosts;

  allow_cors();

  define('GITHUB_ROOT', 'https://github.com/keymanapp/keyboards/tree/master/');
  define('CDN_ROOT', KeymanHosts::Instance()->s_keyman_com . '/');

  // Legacy region integer values
  $regions = array(
    "World" => 1,
    "Africa" => 2,
    "Asia" => 3,
    "Europe" => 4,
    "Americas" => 6,
    "Pacific" => 7
  );

  if(isset($_REQUEST['jsonp'])) {
    $wrap = TRUE;
    $jsonp = $_REQUEST['jsonp'];
    javascript_response();
  } else {
    $wrap = FALSE;
    json_response();
  }

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

  //
  // If the callback is tavultesoft.keymanweb.register, then we make
  // some assumptions about the data required for a smaller result
  //
  $kmw = ($wrap && ($jsonp == 'tavultesoft.keymanweb.register' || $jsonp == 'keyman.register'));
  if($kmw) {
    if(isset($_REQUEST['timerid'])) $timerid = $_REQUEST['timerid'];
    $keyboardlist=explode(",",$keyboardid);
  }

  if(isset($_REQUEST['device'])) {
    $device = strtolower($_REQUEST['device']);
  } else {
    $device = 'any';
  }

  switch($device) {
    case 'windows':
    case 'macosx':
    case 'desktop':
    case 'iphone':
    case 'ipad':
    case 'androidphone':
    case 'androidtablet':
    case 'tablet':
      break;
    case 'phone':
    case 'mobile':    // BUG in interface, should be using 'phone', keep for back-compat
      $device = 'phone';
      break;
    default:
      $device = 'any';
  }

  $dateFormatSeconds = isset($_REQUEST['dateformat']) && $_REQUEST['dateformat'] == 'seconds';

  $options = array(
    'context' => $context,
    'dateFormat' => $dateFormatSeconds ? 'seconds' : 'standard',
    'device' => $device,
    'keyboardBaseUri' => CDN_ROOT . 'keyboard/',
    'fontBaseUri' => CDN_ROOT . 'font/deploy/'
  );

  if(!empty($keyboardid)) {
    $options['keyboardid'] = $keyboardid;
  }
  if(!empty($languageid)) {
    $options['languageid'] = translateLanguageIdToOutputFormat($languageid);
  }
  $options['keyboardVersion'] = 'current';

  $response = array('options' => $options);

  if($context == 'language') {
    if(empty($languageid)) {
      $response['languages'] = getLanguages($languageid, $keyboardid);
    } else {
      $response['language'] = getLanguages($languageid, $keyboardid);
    }
  } else if($context == 'keyboard') {
    if(empty($keyboardid)) {
      $response['keyboard'] = getKeyboards($keyboardid, $languageid);
    } else if($kmw) {
      // Support multiple keyboard requests in a single call, for keymanweb
      $kbddata=array();
      for($i=0; $i<count($keyboardlist); $i++) {
        $kbdspec=explode("@",$keyboardlist[$i]);
        $keyboardid=$kbdspec[0];
        if(count($kbdspec) > 1) $languageid=translate6393ToBCP47($kbdspec[1]); else $languageid='';
        array_push($kbddata,getKeyboards($keyboardid, $languageid));
      }
      $response['keyboard'] = $kbddata;
    } else {
      $response['keyboard'] = getKeyboards($keyboardid, $languageid);
    }
  } else {
    fail('Invalid function', 400);
  }

  if(isset($timerid)) {
    $response['timerid']=$timerid;
  }

  if($wrap) {
    echo $jsonp . '(' . json_encode($response, JSON_UNESCAPED_SLASHES) . ');';
  } else {
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
  }

  function isKeyboardFiltered($keyboard_id) {
    global $isMobileDevice;

    return $isMobileDevice && (
      $keyboard_id == 'european' ||
      $keyboard_id == 'chinese' ||
      $keyboard_id == 'japanese' ||
      $keyboard_id == 'korean_rr');
  }

  function getLanguages($id, $keyboardid) {
    global $device, $kmw;

    $languages = DB_LoadLanguages_0($id);
    $keyboards_0 = DB_LoadLanguages_0_Keyboards($id);
    $keyboards = [];
    foreach($keyboards_0 as $row) {
      $keyboards[$row['keyboard_id']] = $row;
    }

    $reslang = null;
    $reskbds = null;

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

        $reslang = array(
          'name' => $language['language_name'],
          'id' => $langid,
          'region' => mapEthnologueRegionToLegacyRegion($language['legacy_region'])
        );
        $reskbds = array();
        $LastID = $langid;
      }

      if(!empty($keyboardid) && $language['keyboard_id'] != $keyboardid) {
        continue;
      }

      $keyboard = $keyboards[$language['keyboard_id']];

      $reskbd = array(
        'id' => $language['keyboard_id'],
        'name' => $keyboard['name'],
        'filename' => getKeyboardURI($keyboard['keyboard_id'], $keyboard['version']),
        'version' => $keyboard['version']
      );

      $keyboard_info = json_decode($keyboard['keyboard_info']);

      if(isset($keyboard_info->sourcePath)) {
        $reskbd['source'] = GITHUB_ROOT . $keyboard_info->sourcePath;
      }

      if(!$kmw) {
        $reskbd['lastModified'] = dateFormat($keyboard['last_modified']);
        $reskbd['fileSize'] = $keyboard['js_filesize'];
      }


      /*$device_ios = KeyboardInfoPlatformSupportToLegacyDeviceTable($keyboard_info->platformSupport, 'ios');
      $device_android = KeyboardInfoPlatformSupportToLegacyDeviceTable($keyboard_info->platformSupport, 'android');

      $reskbd['devices'] = array(
        'phone' => max($device_ios, $device_android),
        'tablet' => max($device_ios, $device_android),
        'desktop' => KeyboardInfoPlatformSupportToLegacyDeviceTable($keyboard_info->platformSupport, 'desktopWeb')
      );*/

      // TODO: minVersion, maxVersion
      //if(!empty($language->MinKeymanWebVersion)) $reskbd['minVersion'] = $language->MinKeymanWebVersion;
      //if(!empty($language->MaxKeymanWebVersion)) $reskbd['maxVersion'] = $language->MaxKeymanWebVersion;

      addFontAndExample($reskbd, $language['bcp47'], $keyboard_info, $device);

      if((!$kmw) && $keyboard['is_rtl']) {
        $reskbd['rtl'] = true;
      }

      // TODO: Default
      //if($language->DefaultForLanguage)
      //  $reskbd['default'] = true;
      array_push($reskbds, $reskbd);
    }

    if(isset($reslang) && !empty($reslang)) {
      $reslang['keyboards'] = $reskbds;
      array_push($res, $reslang);
    }

    if(empty($id)) {
      if($kmw) return removeKeyboardsFromLanguages($res);
      else return array('languages' => $res);
    }

    if(count($res) == 1) {
      return $res[0];
    }

    return $res;
  }

/**
  * removeKeyboardsFromLanguages
  *
  * @param array $res
  * @returns array
  */
function removeKeyboardsFromLanguages($res) {
  for($i=0; $i < count($res); $i++) {
    if(isset($res[$i]['keyboards'])) unset($res[$i]['keyboards']);// = NULL;
  }
  return $res;
}

/**
* getKeyboardInfo
*
* @param CRM_CloudKeyboardVersion $keyboard
* @param string $languageid
* @param array $allKeyboardLanguages
*/
function getKeyboardInfo($keyboard, $languageid, $allKeyboardLanguages) {
    global $device, $kmw;

    $jskeyboard = array(
      'id' => $keyboard['keyboard_id'],
      'name' => $keyboard['name'],
      'filename' => getKeyboardURI($keyboard['keyboard_id'], $keyboard['version']),
      'version' => $keyboard['version']
    );

    if(isset($keyboard['legacy_id'])) {
      $jskeyboard['desktopKeyboardID'] = $keyboard['legacy_id'];
    }

    $keyboard_info = json_decode($keyboard['keyboard_info']);

    if(isset($keyboard_info->sourcePath)) {
      $jskeyboard['source'] = GITHUB_ROOT . $keyboard_info->sourcePath;
    }

    if(!$kmw) {
      $jskeyboard['lastModified'] = dateFormat($keyboard['last_modified']);
      if($keyboard['is_rtl']) {
        $jskeyboard['rtl'] = true;
      }
    }

    //if(!empty($keyboard->MinKeymanWebVersion)) $jskeyboard['minVersion'] = $keyboard->MinKeymanWebVersion;
    //if(!empty($keyboard->MaxKeymanWebVersion)) $jskeyboard['maxVersion'] = $keyboard->MaxKeymanWebVersion;

    $device_ios = KeyboardInfoPlatformSupportToLegacyDeviceTable($keyboard_info->platformSupport, 'ios');
    $device_android = KeyboardInfoPlatformSupportToLegacyDeviceTable($keyboard_info->platformSupport, 'android');

    $jskeyboard['devices'] = array(
      'phone' => max($device_ios, $device_android),
      'tablet' => max($device_ios, $device_android),
      'desktop' => KeyboardInfoPlatformSupportToLegacyDeviceTable($keyboard_info->platformSupport, 'desktopWeb')
    );

    // Load languages

    $jslanguages = array();
    if(array_key_exists($keyboard['keyboard_id'], $allKeyboardLanguages)) {
      $languages = $allKeyboardLanguages[$keyboard['keyboard_id']];
      $output_languageid = translateLanguageIdToOutputFormat($languageid);
      foreach($languages as $language) {
        $langid = translateLanguageIdToOutputFormat($language['bcp47']);
        if(empty($languageid) || $langid == $output_languageid) {
          $item = array(
            'id' => $langid,
            'name' => $language['name'],
            'region' => mapEthnologueRegionToLegacyRegion($language['legacy_region'])
          );

          addFontAndExample($item, $language['bcp47'], $keyboard_info, $device);
          array_push($jslanguages, $item);
        }
      }
    }

    $jskeyboard['languages'] = $jslanguages;
    return $jskeyboard;
  }

  function getKeyboards($keyboardid, $languageid) {
    if(empty($languageid) && empty($keyboardid)) {
      // All keyboards, root level
      $keyboards = DB_LoadKeyboards(null);
      $allKeyboardLanguages = DB_LoadAllKeyboardLanguages(null);
    } else if(empty($languageid)) {
      // Specific keyboard, root level -- return single keyboard
      $keyboards = DB_LoadKeyboards($keyboardid);
      $allKeyboardLanguages = DB_LoadAllKeyboardLanguages($keyboardid);
      if(sizeof($keyboards) > 0) {
        return getKeyboardInfo($keyboards[0], '', $allKeyboardLanguages);
      }
      fail('Keyboard not found', 404);
    } else if(empty($keyboardid)) {
      // Keyboards for specific language, child level
      $keyboards = DB_LoadKeyboardsForLanguage($languageid);
      $allKeyboardLanguages = DB_LoadAllKeyboardLanguagesByLanguage($languageid);
    } else {
      // Specific keyboard, root level -- return single keyboard
      $keyboards = DB_LoadKeyboards($keyboardid);
      $allKeyboardLanguages = DB_LoadAllKeyboardLanguages($keyboardid);
      if(sizeof($keyboards) > 0) {
        return getKeyboardInfo($keyboards[0], $languageid, $allKeyboardLanguages);
      }
      fail("Keyboard not found", 404);
    }

    $jskeyboards = array();
    foreach($keyboards as $keyboard) {
      // note - following line temp until we have KMW2.0 with device-specific exclusions
      if(isKeyboardFiltered($keyboard['keyboard_id'])) {
        continue;
      }
      $jskeyboard = getKeyboardInfo($keyboard, '', $allKeyboardLanguages);
      array_push($jskeyboards, $jskeyboard);
    }

    return $jskeyboards;
  }

  function getKeyboardURI($name, $version) {
    return "$name/$version/$name-$version.js";
  }

  function keyboardInfoFontToObject($font, $device) {
    $res = [
      "family" => $font->family
    ];

    $source = is_array($font->source) ? $font->source : [$font->source];

    $res['source'] = array_values(array_filter($source, function($s) use ($device) {
        return (fontFilterInvalidForDevice($s, $device) !== '');
      }));

    if(sizeof($res['source']) == 0) {
      return null;
    }
    if(isset($font->size)) {
      $res["size"] = $font->size;
    }
    return $res;
  }

  function keyboardInfoExampleKeysToAPI($keys) {
    if(!is_array($keys)) {
      return $keys;
    }
    // TODO: convert keyboard_info keys array format to string
    return $keys;
  }
  function keyboardInfoExampleToObject($example) {
    return [
      'keys' => empty($example->keys) ? '' : keyboardInfoExampleKeysToAPI($example->keys),
      'text' => empty($example->text) ? '' : $example->text,
      'note' => empty($example->note) ? '' : $example->note
    ];
  }

  function addFontAndExample(&$item, $lang, $keyboard_info, $device) {
    if(is_array($keyboard_info->languages)) {
      return;
    }

    if(!isset($keyboard_info->languages->$lang)) {
      return;
    }

    $jsonlanguage = $keyboard_info->languages->$lang;

    // fontToObject -- oskFont, font
    if(isset($jsonlanguage->font)) {
      $item['font'] = keyboardInfoFontToObject($jsonlanguage->font, $device);
    }

    if(isset($jsonlanguage->oskFont)) {
      $item['oskFont'] = keyboardInfoFontToObject($jsonlanguage->oskFont, $device);
    }

    global $kmw;
    // example -- keys, text, note
    if(!$kmw && isset($jsonlanguage->example)) {
      $item['example'] = keyboardInfoExampleToObject($jsonlanguage->example);
    }
  }

  function mapEthnologueRegionToLegacyRegion($id) {
    global $regions;
    if(array_key_exists($id, $regions)) {
      return $regions[$id];
    }
    return 1; // world
  }

  function KeyboardInfoPlatformSupportToLegacyDeviceTable($platformSupport, $key) {
    $level = isset($platformSupport->$key) ? $platformSupport->$key : 'none';
    switch($level) {
      case 'none': return 0;
      case 'basic': return 1;
      case 'full': return 2;
    }
    return 0;
  }

?>
