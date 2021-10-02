<?php
  namespace Keyman\Site\com\keyman\api;

  require_once(__DIR__ . '/../../tools/util.php');
  require_once(__DIR__ . '/../../tools/keymanversion.php');

  class Version {
    function execute($platform, $level) {
      $keymanVersion = new \keymanversion();

      $ver = $keymanVersion->getVersion($platform, $level);

      if (!empty($ver)) {
        $verdata = array('platform' => $platform, 'level' => $level, 'version' => $ver);
      } else {
        $verdata = array('platform' => $platform, 'level' => $level, 'error' => 'No version exists for given platform and level');
      }

      return $verdata;
    }

    function executeAll($platform) {
      $keymanVersion = new \keymanversion();

      $ver = $keymanVersion->getVersions($platform);
      if (is_object($ver)) {
        $ver->platform = $platform;
        return $ver;
      } else {
        return NULL;
      }
    }
  }