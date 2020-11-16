<?php
  declare(strict_types=1);

  namespace Keyman\Site\com\keyman\api;

  class ReleaseSchedule {

    /**
     * This function helps us to do a gradual roll out of a major upgrade of Keyman
     * on Windows, by testing the current time against both the date of the release
     * and the minute of the hour, so that users checking only at specific times
     * receive the update.
     *
     * @param string releaseDate    yyyy-mm-dd date that major version was released
     * @param int    currentTime    Unix timestamp of current time
     * @return bool  true if the request should be presented with the upgrade
     */
    public static function DoesRequestMeetSchedule(string $releaseDate, int $currentTime = null): bool {
      // This is an arbitrary schedule; see for example Chrome's release schedule:
      // https://chromium.googlesource.com/chromium/src/+/master/docs/process/release_cycle.md
      $schedule = [
        5 => 3,   // 5 days for 3 minutes, 5% of user base
        10 => 6,  // 10 days for 6 minutes / 10%
        15 => 18, // 15 days for 18 minutes / 30%
        20 => 36  // 20 days for 36 minutes / 60%
      ];

      $releaseDate = new \DateTime($releaseDate);
      $currentDate = new \DateTime();
      if($currentTime) {
        $currentDate->setTimestamp($currentTime);
      }

      $currentTime = getdate($currentDate->getTimestamp());

      if($currentDate < $releaseDate) {
        // Don't match if current date before release date
        return FALSE;
      }

      $interval = $currentDate->diff($releaseDate);

      foreach($schedule as $days => $minutes) {
        if($interval->days <= $days) {
          return $currentTime['minutes'] < $minutes;
        }
      }

      // It's been more than 20 days so everyone gets the update now
      return TRUE;
    }
  }
