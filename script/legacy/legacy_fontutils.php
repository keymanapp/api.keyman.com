<?php
  /**
  * fontFilterInvalidForDevice
  *   
  * @param string $source
  * @param string $device
  * @returns string        Source or '', depending on whether source is valid for the target device
  */
  function fontFilterInvalidForDevice($source, $device) {
    switch($device) {
    case 'iphone':
    case 'ipad':
      // iOS doesn't support .eot or .svg font formats
      if(preg_match('/\.eot$/i', $source)) return '';
      if(preg_match('/\.svg/i', $source)) return '';
      break;
    case 'androidphone':
    case 'androidtablet':
      // Android doesn't support .eot or .mobileconfig font formats
      if(preg_match('/\.eot$/i', $source)) return '';
      if(preg_match('/\.mobileconfig/i', $source)) return '';
      break;
    case 'windows':
    case 'macosx': 
    case 'desktop': 
      // .mobileconfig and .svg are only supported on iOS/Android respectively
      //if(preg_match('/\.mobileconfig/i', $source)) return '';
      //if(preg_match('/\.svg/i', $source)) return '';
    }
    return $source;
  }

  /**
  * fontSourceStringToArray
  *   
  * @param string $source
  * @param string $device
  * @returns array
  */
  function fontSourceStringToArray($source, $device) {
    $p = explode(',', $source);
    if(sizeof($p) == 1) {
      return fontFilterInvalidForDevice($source, $device);
      //return $source;
    }
    for($i = sizeof($p) - 1; $i >= 0; $i--) {
      $s = fontFilterInvalidForDevice($p[$i], $device);
      if(empty($s)) array_splice($p, $i, 1);
      else $p[$i] = $s;
    }
    return $p;
  }

  /**
  * fontToObject
  *   
  * @param string $family
  * @param string $size
  * @param string $source
  * @param string $device
  * @returns array
  */
  function fontToObject($family, $size, $source, $device) {
    if(empty($family) && empty($size) && empty($source)) return null;
    $r = array();
    if(!empty($family)) {
      $r['family'] = $family;
    }
    if(!empty($size)) $r['size'] = $size;
    if(!empty($source)) $r['source'] = fontSourceStringToArray($source, $device);
    if(empty($r['source'])) return null;
    return $r;
  }
?>