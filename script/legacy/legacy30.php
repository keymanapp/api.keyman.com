<?php
  // Mimic the KeymanWeb Cloud json 3.0 API but from our t_keyboards data

  require_once('../../tools/util.php');
  require_once('legacy_db.php');
  require_once('legacy_fontutils.php');
  require_once('legacy_utils.php');
  
  allow_cors();
  json_response();

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

  if(isset($_REQUEST['device'])) {
    $device = strtolower($_REQUEST['device']); 
  } else {
    $device = 'any';
  }
  
  switch($device) {
    case 'windows':
    case 'macosx': 
    case 'desktop': 
      $isMobileDevice = false; break;
    case 'iphone':
    case 'ipad':
    case 'androidphone':
    case 'androidtablet':
    case 'mobile':
    case 'tablet':
      $isMobileDevice = true; break;
    default:
      $isMobileDevice = false;
      $device = 'any';
  }
  
  $dateFormatSeconds = isset($_REQUEST['dateformat']) && $_REQUEST['dateformat'] == 'seconds';
  
  $options = array(
    'context' => $context,
    'dateFormat' => $dateFormatSeconds ? 'seconds' : 'standard',
    'device' => $device,
    'keyboardBaseUri' => 'https://s.keyman.com/keyboard/',
    'fontBaseUri' => 'https://s.keyman.com/font/deploy/'
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
    } else {
      $response['keyboard'] = getKeyboards($keyboardid, $languageid);
    }
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
  
  function getLanguages($id, $keyboardid) {
    global $device;

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
        $reslang = array('name' => $language['language_name'], 'id' => $langid);
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
        'lastModified' => dateFormat($keyboard['last_modified']),
        'version' => $keyboard['version'],
        'fileSize' => $keyboard['js_filesize']);
      
      $keyboard_info = json_decode($keyboard['keyboard_info']);

      // TODO: minVersion, maxVersion
      //if(!empty($language->MinKeymanWebVersion)) $reskbd['minVersion'] = $language->MinKeymanWebVersion;
      //if(!empty($language->MaxKeymanWebVersion)) $reskbd['maxVersion'] = $language->MaxKeymanWebVersion;
            
      addFontAndExample($reskbd, $language['bcp47'], $keyboard_info, $device);
      
      if($keyboard['is_rtl']) {
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
      return array('languages' => $res);
    }
    
    if(count($res) == 1) {
      return $res[0];
    }
      
    return $res;
  }
  
/**
* getKeyboardInfo
*   
* @param CRM_CloudKeyboardVersion $keyboard
* @param string $languageid
* @param CRM_AllKeyboardLanguages $allKeyboardLanguages
*/
  function getKeyboardInfo($keyboard, $languageid, $allKeyboardLanguages) {
    global $device;
    
    $jskeyboard = array(
      'id' => $keyboard['keyboard_id'], 
      'filename' => getKeyboardURI($keyboard['keyboard_id'], $keyboard['version']), 
      'version' => $keyboard['version'],
      'lastModified' => dateFormat($keyboard['last_modified'])
    );

    if($keyboard['is_rtl']) {
      $jskeyboard['rtl'] = true;
    }

    //if(!empty($keyboard->MinKeymanWebVersion)) $jskeyboard['minVersion'] = $keyboard->MinKeymanWebVersion;
    //if(!empty($keyboard->MaxKeymanWebVersion)) $jskeyboard['maxVersion'] = $keyboard->MaxKeymanWebVersion;

    $keyboard_info = json_decode($keyboard['keyboard_info']);
    
    // Load languages
    $jslanguages = array();
    $languages = $allKeyboardLanguages[$keyboard['keyboard_id']];
    foreach($languages as $language) {
      $item = array(
        'id' => translateLanguageIdToOutputFormat($language['bcp47']), 
        'name' => $language['name']
      );
      
      addFontAndExample($item, $language['bcp47'], $keyboard_info, $device);
      array_push($jslanguages, $item);
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
      'keys' => keyboardInfoExampleKeysToAPI($example->keys), 
      'text' => $example->text, 
      'note' => $example->note
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
    
    // example -- keys, text, note
    if(isset($jsonlanguage->example)) {
      $item['example'] = keyboardInfoExampleToObject($jsonlanguage->example);
    }
  }
  
?>
