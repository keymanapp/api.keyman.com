<?php

  function dateFormat($date) {
    global $dateFormatSeconds;
    if($dateFormatSeconds) {
      return strtotime($date);
    } else {
      return $date;
    }
  }
  
  // This data is constructed from the standards data. It should never change.
  // Extracted from http://www.iana.org/assignments/language-subtag-registry/language-subtag-registry

  $map6393tobcp47 = [
    'aar' => 'aa',
    'abk' => 'ab',
    'afr' => 'af',
    'aka' => 'ak',
    'amh' => 'am',
    'ara' => 'ar',
    'arg' => 'an',
    'asm' => 'as',
    'ava' => 'av',
    'ave' => 'ae',
    'aym' => 'ay',
    'aze' => 'az',
    'bak' => 'ba',
    'bam' => 'bm',
    'bel' => 'be',
    'ben' => 'bn',
    'bis' => 'bi',
    'bod' => 'bo',
    'bos' => 'bs',
    'bre' => 'br',
    'bul' => 'bg',
    'cat' => 'ca',
    'ces' => 'cs',
    'cha' => 'ch',
    'che' => 'ce',
    'chu' => 'cu',
    'chv' => 'cv',
    'cor' => 'kw',
    'cos' => 'co',
    'cre' => 'cr',
    'cym' => 'cy',
    'dan' => 'da',
    'deu' => 'de',
    'div' => 'dv',
    'dzo' => 'dz',
    'ell' => 'el',
    'eng' => 'en',
    'epo' => 'eo',
    'est' => 'et',
    'eus' => 'eu',
    'ewe' => 'ee',
    'fao' => 'fo',
    'fas' => 'fa',
    'fij' => 'fj',
    'fin' => 'fi',
    'fra' => 'fr',
    'fry' => 'fy',
    'ful' => 'ff',
    'gla' => 'gd',
    'gle' => 'ga',
    'glg' => 'gl',
    'glv' => 'gv',
    'grn' => 'gn',
    'guj' => 'gu',
    'hat' => 'ht',
    'hau' => 'ha',
    'hbs' => 'sh',
    'heb' => 'he',
    'her' => 'hz',
    'hin' => 'hi',
    'hmo' => 'ho',
    'hrv' => 'hr',
    'hun' => 'hu',
    'hye' => 'hy',
    'ibo' => 'ig',
    'ido' => 'io',
    'iii' => 'ii',
    'iku' => 'iu',
    'ile' => 'ie',
    'ina' => 'ia',
    'ind' => 'id',
    'ipk' => 'ik',
    'isl' => 'is',
    'ita' => 'it',
    'jav' => 'jv',
    'jpn' => 'ja',
    'kal' => 'kl',
    'kan' => 'kn',
    'kas' => 'ks',
    'kat' => 'ka',
    'kau' => 'kr',
    'kaz' => 'kk',
    'khm' => 'km',
    'kik' => 'ki',
    'kin' => 'rw',
    'kir' => 'ky',
    'kom' => 'kv',
    'kon' => 'kg',
    'kor' => 'ko',
    'kua' => 'kj',
    'kur' => 'ku',
    'lao' => 'lo',
    'lat' => 'la',
    'lav' => 'lv',
    'lim' => 'li',
    'lin' => 'ln',
    'lit' => 'lt',
    'ltz' => 'lb',
    'lub' => 'lu',
    'lug' => 'lg',
    'mah' => 'mh',
    'mal' => 'ml',
    'mar' => 'mr',
    'mkd' => 'mk',
    'mlg' => 'mg',
    'mlt' => 'mt',
    'mon' => 'mn',
    'mri' => 'mi',
    'msa' => 'ms',
    'mya' => 'my',
    'nau' => 'na',
    'nav' => 'nv',
    'nbl' => 'nr',
    'nde' => 'nd',
    'ndo' => 'ng',
    'nep' => 'ne',
    'nld' => 'nl',
    'nno' => 'nn',
    'nob' => 'nb',
    'nor' => 'no',
    'nya' => 'ny',
    'oci' => 'oc',
    'oji' => 'oj',
    'ori' => 'or',
    'orm' => 'om',
    'oss' => 'os',
    'pan' => 'pa',
    'pli' => 'pi',
    'pol' => 'pl',
    'por' => 'pt',
    'pus' => 'ps',
    'que' => 'qu',
    'roh' => 'rm',
    'ron' => 'ro',
    'run' => 'rn',
    'rus' => 'ru',
    'sag' => 'sg',
    'san' => 'sa',
    'sin' => 'si',
    'slk' => 'sk',
    'slv' => 'sl',
    'sme' => 'se',
    'smo' => 'sm',
    'sna' => 'sn',
    'snd' => 'sd',
    'som' => 'so',
    'sot' => 'st',
    'spa' => 'es',
    'sqi' => 'sq',
    'srd' => 'sc',
    'srp' => 'sr',
    'ssw' => 'ss',
    'sun' => 'su',
    'swa' => 'sw',
    'swe' => 'sv',
    'tah' => 'ty',
    'tam' => 'ta',
    'tat' => 'tt',
    'tel' => 'te',
    'tgk' => 'tg',
    'tgl' => 'tl',
    'tha' => 'th',
    'tir' => 'ti',
    'ton' => 'to',
    'tsn' => 'tn',
    'tso' => 'ts',
    'tuk' => 'tk',
    'tur' => 'tr',
    'twi' => 'tw',
    'uig' => 'ug',
    'ukr' => 'uk',
    'urd' => 'ur',
    'uzb' => 'uz',
    'ven' => 've',
    'vie' => 'vi',
    'vol' => 'vo',
    'wln' => 'wa',
    'wol' => 'wo',
    'xho' => 'xh',
    'yid' => 'yi',
    'yor' => 'yo',
    'zha' => 'za',
    'zho' => 'zh',
    'zul' => 'zu'
  ];
  $mapbcp47to6393 = null;
  
  function translate6393ToBCP47($id) {
    // This function just maps ISO639-3 codes to 2 letter codes where one exists, otherwise
    // returns the three letter code.
    global $map6393tobcp47;
    if(isset($map6393tobcp47[$id])) {
      return $map6393tobcp47[$id];
    }
    return $id;
  }
  
  function build_mapbcp47to6393() {
    global $map6393tobcp47, $mapbcp47to6393;
    $mapbcp47to6393 = [];
    foreach($map6393tobcp47 as $iso6393 => $bcp47) {
      $mapbcp47to6393[$bcp47] = $iso6393;
    }
  }

  function translateLanguageIdToOutputFormat($id) {
    global $use_bcp47;
    if(!isset($use_bcp47)) {
      $use_bcp47 = isset($_REQUEST['languageidtype']) && $_REQUEST['languageidtype'] == 'bcp47';
    }
    
    if($use_bcp47) {
      return $id;
    }

    if(empty($id)) return $id;

    global $map6393tobcp47, $mapbcp47to6393;
    if(empty($mapbcp47to6393)) {
      build_mapbcp47to6393();
    }
    
    $id = explode('-', $id);
    return isset($mapbcp47to6393[$id[0]]) ? $mapbcp47to6393[$id[0]] : $id[0];
  }
  
  function validateVersion($v) {
    global $version, $version1, $version2;
    // Make sure we have a valid version string and return 9.0 if not (based on Desktop version).
    if(!preg_match('/^\d+\.\d+(\.\d+)*$/', $v)) {
      $version = '9.0';
    } else {
      $version = $v;
    }
    preg_match('/^(\d+)\.(\d+)/', $version, $matches);
    $version1 = $matches[1];
    /* Because we don't really support KMW keyboards earlier than 2.0, that maps to Keyman Desktop 9.0, we
       set the version to 9.0 if it is lower than that. */
    if($version1 < 9) $version1 = 9;
    $version2 = $matches[2];
  }

?>
