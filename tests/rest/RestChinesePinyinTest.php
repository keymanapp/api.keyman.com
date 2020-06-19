<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests\rest;

require_once(__DIR__ . '/../../tools/base.inc.php');
require_once(__DIR__ . '/../TestUtils.inc.php');
require_once(__DIR__ . '/../TestDBBuild.inc.php');
require_once(__DIR__ . '/RestTestCase.php');

use Keyman\Site\com\keyman\api\tests\TestUtils;

final class RestChinesePinyinTest extends RestTestCase
{
  static function setUpBeforeClass(): void
  {
    \Keyman\Site\com\keyman\api\tests\TestDBBuild::Build();
  }

  public function testSearchForBei(): void
  {
    $response = $this->http->request('GET', '/script/cjk/chinese_pinyin.php?id=1&py=bei');
    $this->assertStandardJavascriptRestResponses($response);
    $body = $response->getBody()->getContents();
    $this->assertStringEqualsFile(__DIR__ . '/../fixtures/chinese_pinyin-bei.js', $body);
  }

  public function testSearchForTa(): void
  {
    $response = $this->http->request('GET', '/script/cjk/chinese_pinyin.php?id=99&py=ta');
    $this->assertStandardJavascriptRestResponses($response);
    $body = $response->getBody()->getContents();
    $this->assertStringEqualsFile(__DIR__ . '/../fixtures/chinese_pinyin-ta.js', $body);
  }
}