<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests;

require_once(__DIR__ . '/../tools/base.inc.php');
require_once(__DIR__ . '/TestUtils.inc.php');

use PHPUnit\Framework\TestCase;

final class CommonFilesTest extends TestCase
{
  const commonDir = __DIR__ . '/../_common/';
  const sites = [['keyman.com'], ['help.keyman.com']];

  public function dataSites() {
    return self::sites;
  }
  /**
   * Verifies that files in _common folder are identical. In the future, we may use a different way of syncing the common files,
   * (composer module?, git submodule, or the like) but for now this keeps us in sync at reasonably little cost.
   *
   * @dataProvider dataSites
   */
  public function testCommonFilesAreIdentical($site): void
  {
    $this->setName("testCommonFilesAreIdenticalTo $site");

    $localFiles = glob(self::commonDir . "*");

    if(file_exists(__DIR__ . "/../../$site")) {
      $remoteDir = __DIR__ . "/../../$site/_common/";
      foreach($localFiles as $localFile) {
        $remoteFile = $remoteDir . basename($localFile);
        $this->assertFileEquals($localFile, $remoteFile, "$localFile != $remoteFile");
      }

      $remoteFiles = glob($remoteDir . "*");
      foreach($remoteFiles as $remoteFile) {
        $localFile = self::commonDir . basename($remoteFile);
        $this->assertFileExists($localFile, "$localFile does not exist");
      }

    } else {
      // On CI, we don't have access to other sites
      $this->markTestSkipped("Skipping because we don't have access to site $site");
    }
  }
}