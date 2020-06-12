<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/../TestUtils.inc.php');
require_once(__DIR__ . '/../TestDBBuild.inc.php');
require_once(__DIR__ . '/RestTestCase.php');

use Keyman\Site\com\keyman\api\tests\TestUtils;

final class RestJapanesePinyinTest extends RestTestCase
{
  static function setUpBeforeClass(): void
  {
    \Keyman\Site\com\keyman\api\tests\TestDBBuild::Build();
  }

  public function testSearchForLove(): void
  {
    $response = $this->http->request('GET', '/script/cjk/japanese.php?kana=%E3%81%82%E3%81%84%E3%81%98&id=100');
    $this->assertStandardJavascriptRestResponses($response);
    $body = $response->getBody()->getContents();
    $this->assertStringEqualsFile(__DIR__ . '/../fixtures/japanese-love.js', $body);
  }
}