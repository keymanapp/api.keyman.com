<?php
  declare(strict_types=1);

  namespace Keyman\Site\com\keyman\api;

  use Keyman\Site\Common\KeymanHosts;

  class KeymanUrls {

    //
    // Hard-coded path to keyboards so we don't have to query
    // downloads.keyman.com/api/keyboard for each keyboard
    //
    public static function keyboard_download_url($id, $version, $package): string
    {
      return KeymanHosts::Instance()->keyman_com . "/go/package/download/keyboard/" .
        rawurlencode($id) .
        "?" .
        (empty($version) ? "" : "version=" . rawurlencode($version) . "&") .
        "update=1";
    }

    //
    // Hard-coded path to models so we don't have to query
    // downloads.keyman.com/api/keyboard for each keyboard
    //
    public static function model_download_url($id, $version, $package): string
    {
      return KeymanHosts::Instance()->keyman_com . "/go/package/download/model/" .
        rawurlencode($id) .
        "?" .
        (empty($version) ? "" : "version=" . rawurlencode($version) . "&") .
        "update=1";
    }
  }
