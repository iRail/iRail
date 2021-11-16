<?php

namespace Irail\Data\Nmbs\Tools;

use Carbon\Carbon;
use DateTime;
use Exception;
use Irail\Data\Nmbs\Models\Station;

/** Copyright (C) 2011 by iRail vzw/asbl
 * This is a class with static tools for you to use on the NMBS scraper. It contains stuff that is needed by all other classes.
 */
class Tools
{

    /**
     * Parse a "traffic day time", which can go beyond 24h. Example: 01002000 (24:02:00, 00:02:00 the next day)
     *
     * @param DateTime $baseDate The date of the traffic day (the date of the first departure of the vehicle)
     * @param string   $time The time on the traffic day
     * @return Carbon A DateTime object representing this time.
     * @throws Exception
     */
    public static function parseDDHHMMSS(DateTime $baseDate, string $time): Carbon
    {
        if (strlen($time) < 6) {
            throw new Exception("Invalid time passed to parseTrafficDayTime, should be 6 or 8 digits!");
        }
        if (strlen($time) == 6) {
            $time = '00' . $time;
        }
        date_default_timezone_set('Europe/Brussels');
        $dayoffset = intval(substr($time, 0, 2));
        $hour = intval(substr($time, 2, 2));
        if ($hour > 23) {
            // Implement this functionality when needed
            throw new \InvalidArgumentException("Traffic day times in hhmmss format, with an hour component larger than 23, cannot be parsed at this moment");
        }
        $minute = intval(substr($time, 4, 2));
        $second = intval(substr($time, 6, 2));
        $baseDate = new Carbon($baseDate);
        return $baseDate->addDays($dayoffset)->setTime($hour, $minute, $second);
    }

    /**
     * Calculate the difference between two moments, in seconds.
     * @param string $realTimeTime -> in 00d15:24:00 or hhmmss or ddhhmmss format
     * @param string $realTimeDate Date as a Ymd string.
     * @param string $plannedTime -> in 00d15:24:00 or hhmmss or ddhhmmss format
     * @param string $plannedDate Date as a Ymd string.
     * @return int The difference between the two datetimes, in seconds.
     */
    public static function calculateSecondsHHMMSS($realTimeTime, $realTimeDate, $plannedTime, $plannedDate)
    {
        return Tools::transformTime($realTimeTime, $realTimeDate) - Tools::transformTime($plannedTime, $plannedDate);
    }

    public static function calculateDDHHMMSSTimeDifferenceInSeconds(DateTime $baseDate, string $scheduledTime, string $realtime): int
    {
        return self::parseDDHHMMSS($baseDate, $scheduledTime)->diffInRealSeconds(self::parseDDHHMMSS($baseDate, $realtime));
    }


    public static function createDepartureUri(Station $station, $departureTime, string $vehicleId): string
    {
        return 'http://irail.be/connections/' . substr(
            basename($station->{'@id'}),
            2
        ) . '/' . date(
            'Ymd',
            $departureTime
        ) . '/' . substr(
            $vehicleId,
            strrpos($vehicleId, '.') !== false ? strrpos($vehicleId, '.') + 1 : 0
        );
    }

    public static function getUserAgent(): string
    {
        return ;
    }

    /**
     * Send a HTTP response header to the requester, idicating that this response was served from an internal cache.
     * @param bool $cached
     */
    public static function sendIrailCacheResponseHeader(bool $cached) : void
    {
        header("X-iRail-cache-hit: " . $cached);
    }


}
