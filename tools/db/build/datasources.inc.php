<?php
  // Data sources

  class DBDataSources {
    // http://www-01.sil.org/iso639-3/iso-639-3.tab <-- for iso639-3 -> iso639-1 mappings
    // http://www-01.sil.org/iso639-3/iso-639-3_Name_Index.tab <-- for language name index
    // https://www.iana.org/assignments/language-subtag-registry/language-subtag-registry <-- for language subtag registry
    public const URI_LANGUAGE_SUBTAG_REGISTRY = 'https://www.iana.org/assignments/language-subtag-registry/language-subtag-registry';
    public const URI_ISO639_3_TAB = 'https://iso639-3.sil.org/sites/iso639-3/files/downloads/iso-639-3.tab';
    public const URI_ISO639_3_NAME_INDEX_TAB = 'https://iso639-3.sil.org/sites/iso639-3/files/downloads/iso-639-3_Name_Index.tab';

    // No longer using ethnologue live data as it's not accessible programatically (behind Cloudflare DDos protection which
    // effectively blocks scripted access).
    public const ETHNOLOGUE_LANGUAGE_CODES_TAB = __DIR__ . '/../../../static-data/ethnologue_language_codes.tab'; // 'https://www.ethnologue.com/sites/default/files/LanguageCodes.tab';
    public const ETHNOLOGUE_COUNTRY_CODES_TAB = __DIR__ . '/../../../static-data/ethnologue_country_codes.tab'; // 'https://www.ethnologue.com/sites/default/files/CountryCodes.tab';
    public const ETHNOLOGUE_LANGUAGE_INDEX_TAB = __DIR__ . '/../../../static-data/ethnologue_language_index.tab'; // 'https://www.ethnologue.com/sites/default/files/LanguageIndex.tab';

    public const LANGTAGS = 'https://ldml.api.sil.org/langtags.json';

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
