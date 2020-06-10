<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
//require_once(__DIR__ . '/../script/search/search.inc.php');
require_once(__DIR__ . '/TestUtils.inc.php');
require_once(__DIR__ . '/TestDBBuild.inc.php');

use PHPUnit\Framework\TestCase;

final class Legacy10Test extends TestCase
{
  private const SchemaFilename = "/search/1.0.2/search.json";

  static function setUpBeforeClass(): void
  {
    TestDBBuild::Build();
  }

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    $this->markTestIncomplete(
      'Finish this'
    );
  }
}