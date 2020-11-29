<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests {
  require_once(__DIR__ . '/../tools/base.inc.php');
  require_once(__DIR__ . '/../script/package-version/package-version.inc.php');
  require_once(__DIR__ . '/TestUtils.inc.php');
  require_once(__DIR__ . '/TestDBBuild.inc.php');

    use Keyman\Site\com\keyman\api\ReleaseSchedule;
    use PHPUnit\Framework\TestCase;

  final class ReleaseScheduleTest extends TestCase
  {
    public function testReleaseScheduleIsValid(): void
    {
      // mktime params: hour minute second month day year (seriously... can you imagine a worse order oh and year can be 2 digits)
      $this->assertFalse(ReleaseSchedule::DoesRequestMeetSchedule('2020-01-01', mktime(0, 0, 0, 12, 30, 2019)), 'current time before release date should not meet schedule');

      $this->assertTrue(ReleaseSchedule::DoesRequestMeetSchedule('2020-01-01', mktime(0, 0, 0, 1, 1, 2020)), 'current time same as release date should meet schedule for 0 minutes');
      $this->assertFalse(ReleaseSchedule::DoesRequestMeetSchedule('2020-01-01', mktime(0, 15, 0, 1, 1, 2020)), 'current time same as release date should not meet schedule for 15 minutes');

      $this->assertTrue(ReleaseSchedule::DoesRequestMeetSchedule('2020-01-01', mktime(0, 0, 0, 1, 6, 2020)), '5 days after release should meet schedule for 0 minutes');
      $this->assertFalse(ReleaseSchedule::DoesRequestMeetSchedule('2020-01-01', mktime(0, 7, 0, 1, 6, 2020)), '5 days after release should not meet schedule for 7 minutes');

      $this->assertTrue(ReleaseSchedule::DoesRequestMeetSchedule('2020-01-01', mktime(0, 13, 0, 1, 16, 2020)), '15 days after release should meet schedule for 13 minutes');
      $this->assertFalse(ReleaseSchedule::DoesRequestMeetSchedule('2020-01-01', mktime(0, 59, 0, 1, 16, 2020)), '15 days after release should meet schedule for 13 minutes');

      $this->assertTrue(ReleaseSchedule::DoesRequestMeetSchedule('2020-01-01', mktime(0, 59, 0, 1, 23, 2020)), '22 days after release should meet schedule for 59 minutes');
    }
  }
}
