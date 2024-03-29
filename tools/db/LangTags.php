<?php declare(strict_types=1);

  namespace Keyman\Site\com\keyman\api;

  class LangTags {
    const
      TAGTYPE_TAG = 0, /* Base tag */
      TAGTYPE_ALTERNATE = 1,
      TAGTYPE_VARIANT = 2,
      TAGTYPE_WINDOWS = 3,
      TAGTYPE_FULL = 4,
      TAGTYPE_CUSTOM = 5; // created by a custom keyboard_info language entry

    const
      NAMETYPE_NAME = 0,
      NAMETYPE_LOCAL = 1,
      NAMETYPE_LATN = 2,
      NAMETYPE_IANA = 3,
      NAMETYPE_CUSTOM = 4; // created by a custom keyboard_info language entry
  }