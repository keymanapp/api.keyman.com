<?php
  // Data sources

  class DBDataSources {
    // http://www-01.sil.org/iso639-3/iso-639-3.tab <-- for iso639-3 -> iso639-1 mappings
    // http://www-01.sil.org/iso639-3/iso-639-3_Name_Index.tab <-- for language name index
    // https://www.iana.org/assignments/language-subtag-registry/language-subtag-registry <-- for language subtag registry
    public const URI_LANGUAGE_SUBTAG_REGISTRY = 'https://www.iana.org/assignments/language-subtag-registry/language-subtag-registry';
    public const URI_ISO639_3_TAB = 'http://www-01.sil.org/iso639-3/iso-639-3.tab';
    public const URI_ISO639_3_NAME_INDEX_TAB = 'http://www-01.sil.org/iso639-3/iso-639-3_Name_Index.tab';
    public const ETHNOLOGUE_LANGUAGE_CODES_TAB = 'https://www.ethnologue.com/sites/default/files/LanguageCodes.tab';
    public const ETHNOLOGUE_COUNTRY_CODES_TAB = 'https://www.ethnologue.com/sites/default/files/CountryCodes.tab';
    public const ETHNOLOGUE_LANGUAGE_INDEX_TAB = 'https://www.ethnologue.com/sites/default/files/LanguageIndex.tab';

    //define('LANGTAGS', 'https://ldml.api.sil.org/langtags.json');
    // 2020-05-25: LANGTAGS 1.1.1 is currently in staging. Once it hits release, use link above instead.
    // We want the windows and suppress tags which are only in 1.1.1
    public const LANGTAGS = 'https://raw.githubusercontent.com/silnrsi/langtags/master/pub/langtags.json';

    public const URI_KEYBOARD_INFO_ZIP = 'https://downloads.keyman.com/data/keyboard_info.zip';
    public const URI_MODEL_INFO_ZIP = 'https://downloads.keyman.com/data/model_info.zip';

    public $uriLanguageSubtagRegistry = DBDataSources::URI_LANGUAGE_SUBTAG_REGISTRY;
    public $uriIso6393 = DBDataSources::URI_ISO639_3_TAB;
    public $uriIso6393NameIndex = DBDataSources::URI_ISO639_3_NAME_INDEX_TAB;
    public $uriEthnologueLanguageCodes = DBDataSources::ETHNOLOGUE_LANGUAGE_CODES_TAB;
    public $uriEthnologueCountryCodes = DBDataSources::ETHNOLOGUE_COUNTRY_CODES_TAB;
    public $uriEthnologueLanguageIndex = DBDataSources::ETHNOLOGUE_LANGUAGE_INDEX_TAB;
    public $uriLangTags = DBDataSources::LANGTAGS;
    public $uriKeyboardInfo = DBDataSources::URI_KEYBOARD_INFO_ZIP;
    public $uriModelInfo = DBDataSources::URI_MODEL_INFO_ZIP;

    public $mockAnalyticsSqlFile = NULL;

    public function downloadDate($uri) {
      return time();
    }
  }
