<?php

  require_once('../tools/db/build/datasources.inc.php');
  require_once('../tools/db/build/build.inc.php');

  final class TestDBDataSources extends DBDataSources {
    function __construct() {
      $this->uriLanguageSubtagRegistry = $this->fileFromTestDataDir($this->uriLanguageSubtagRegistry);
      $this->uriIso6393 = $this->fileFromTestDataDir($this->uriIso6393);
      $this->uriIso6393NameIndex = $this->fileFromTestDataDir($this->uriIso6393NameIndex);
      $this->uriEthnologueLanguageCodes = $this->fileFromTestDataDir($this->uriEthnologueLanguageCodes);
      $this->uriEthnologueLanguageCodes = $this->fileFromTestDataDir($this->uriEthnologueLanguageCodes);
      $this->uriEthnologueLanguageIndex = $this->fileFromTestDataDir($this->uriEthnologueLanguageIndex);
      $this->uriLangTags = $this->fileFromTestDataDir($this->uriLangTags);
      $this->uriKeyboardInfo = $this->fileFromTestDataDir($this->uriKeyboardInfo);
      $this->uriModelInfo = $this->fileFromTestDataDir($this->uriModelInfo);
    }

    private function fileFromTestDataDir($uri) {
      return __DIR__ . '/data/' . basename($uri);
    }
  }

  class TestDBBuild {
    static function Build() {
      // First, test the existing database to see its data sources
      global $mssql;

      $DBDataSources = new TestDBDataSources();

      $q = $mssql->query("SELECT uri FROM t_dbdatasources WHERE filename = 'langtags.json'");
      $data = $q->fetchAll();
      if(sizeof($data) == 1 && $data[0]->uri === $DBDataSources->uriLangTags) return;

      // Database sources are not from our test resources, so rebuild them
      BuildDatabase($DBDataSources, $mssql, true);
      BuildCJKTables($DBDataSources, $mssql, true);
    }
  }
