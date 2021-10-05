<?php
  // Loads all base requirements
  // Should be included in all scripts
  require_once __DIR__ . '/../vendor/autoload.php';
  require_once __DIR__ . '/autoload.php';

  const SENTRY_DSN = 'https://086e772c0222410db4727ee3b8db9dae@o1005580.ingest.sentry.io/5983517';
  \Keyman\Site\Common\KeymanSentry::Init(SENTRY_DSN);
