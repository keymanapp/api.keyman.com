<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
require_once(__DIR__ . '/TestUtils.inc.php');
require_once(__DIR__ . '/TestDBBuild.inc.php');

use Keyman\Site\Common\KeymanHosts;
use PHPUnit\Framework\TestCase;

class ApiTestCase extends TestCase
{
  private const TIER_TXT = __DIR__ . '/../tools/tier.txt';
  private static bool $wroteTierTxt = false;

  static function setUpBeforeClass(): void
  {
    TestDBBuild::Build();

    // We want to put the website into test mode, if it isn't already,
    // in order to guarantee that we get the mocks on the http endpoints.

    // This assumes that tier.txt is not already present, so if it is,
    // that will override the tier. (On all tiers, it shouldn't really
    // be present, but a developer may choose to put it in place to run
    // tests against live rest endpoints).
    if(!file_exists(self::TIER_TXT)) {
      file_put_contents(self::TIER_TXT, KeymanHosts::TIER_TEST);
      self::$wroteTierTxt = true;
      KeymanHosts::Rebuild();
    }
  }

  static function tearDownAfterClass(): void
  {
    // If we put the website into test mode, then take it out again :)
    if(self::$wroteTierTxt && file_exists(self::TIER_TXT)) unlink(self::TIER_TXT);
    self::$wroteTierTxt = false;
  }
}