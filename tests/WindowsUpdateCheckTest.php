<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
require_once(__DIR__ . '/../script/windows/14.0/update/WindowsUpdateCheck.php');
require_once(__DIR__ . '/TestUtils.inc.php');
require_once(__DIR__ . '/TestDBBuild.inc.php');

use Keyman\Site\com\keyman\api\DownloadsApi;
use PHPUnit\Framework\TestCase;

final class WindowsUpdateCheckTest extends TestCase
{
  private const SchemaFilename = "/windows-update/14.0/windows-update.json";
  private $downloadsApiStub;

  static function setUpBeforeClass(): void
  {
    TestDBBuild::Build();

    // TODO: we need to mock the api for downloads.keyman.com in order to test this properly
    // TODO: figure out URLs for dev vs test vs staging vs production
  }

  public function setUp(): void {
    $this->downloadsApiStub = $this->createMock(DownloadsApi::class);
    $data = json_decode(file_get_contents(__DIR__ . '/fixtures/WindowsUpdateCheck.14.0.DownloadsApi.Platform.json'));
    $this->downloadsApiStub->method('GetPlatformVersion')->willReturn($data);
    $data = json_decode(file_get_contents(__DIR__ . '/fixtures/WindowsUpdateCheck.14.0.DownloadsApi.Keyboard.json'));
    $this->downloadsApiStub->method('GetKeyboardVersion')->willReturn($data);
  }

  public function testSimpleResultValidatesAgainstSchema(): void
  {
    $schema = TestUtils::LoadJSONSchema(self::SchemaFilename);

    $u = new \Keyman\Site\com\keyman\api\WindowsUpdateCheck($this->downloadsApiStub);
    $json = $u->execute('alpha', '14.0.100', ['khmer_angkor'=>'1.0.2', 'foo'=>'0.1']);
    $this->assertNotEmpty($json);
    $data = json_decode($json);
    $this->assertNotNull($data);

    // This will throw an exception if it does not pass
    $schema->in($data);

    // TODO: add some more realism!
    $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/WindowsUpdateCheck.14.0.json', $json);
  }
}