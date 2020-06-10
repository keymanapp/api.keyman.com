<?php
  // Loads all base requirements
  // Should be included in all scripts
  require __DIR__ . '/../vendor/autoload.php';

  function _init_Sentry() {
    if(isset($_SERVER['SERVER_NAME'])) {
      // running from web server
      $host = $_SERVER['SERVER_NAME'];
      if(preg_match('/\.local$/', $host))
        // If the host name is, e.g. api.keyman.com.local, then we'll assume this is a development environment
        $environment = 'development';
      else if(preg_match('/^staging/', $host))
        $environment = 'staging';
      else
        $environment = 'production';
    } else if(isset($_ENV['WEBSITE_SITE_NAME'])) {
      // probably running from CLI in Azure (e.g. deployment or SCM)
      $host = $_ENV['WEBSITE_SITE_NAME'];
      $environment = preg_match('/^staging/', $host) ? 'staging' : 'production';
    } else {
      // Unknown environment, probably local development CLI
      $environment = 'development';
    }

    Sentry\init([
      'dsn' => 'https://086e772c0222410db4727ee3b8db9dae@sentry.keyman.com/4',
      'environment' => $environment
    ]);
  }

  _init_Sentry();
