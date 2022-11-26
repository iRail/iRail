<?php

namespace Irail\Data\Nmbs\Tools;

use Carbon\Carbon;
use DateInterval;
use DateTime;
use Exception;
use Irail\Data\Nmbs\Models\Station;

/** Copyright (C) 2011 by iRail vzw/asbl
 * This is a class with static tools for you to use on the NMBS scraper. It contains stuff that is needed by all other classes.
 */
class Tools
{

    /**
     * @param string $time -> in hh:mm:ss or dd:hh:mm:ss format
     * @param string $date -> in Y-m-d
     * @return int seconds since the Unix epoch
     * @throws Exception
     */
    public static function transformTime(string $time, string $date): int
    {
        if (strlen($time) != 8 && strlen($time) != 11) {
            throw new Exception("Invalid time passed to transformTime, should be 8 or 11 digits! $time");
        }
        if (strlen($time) == 8) {
            $time = '00:' . $time;
        }

        date_default_timezone_set('Europe/Brussels');
        $dayoffset = Tools::safeIntVal(substr($time, 0, 2));
        $hour = Tools::safeIntVal(substr($time, 3, 2));
        $minute = Tools::safeIntVal(substr($time, 6, 2));
        $second = Tools::safeIntVal(substr($time, 9, 2));

        $year = Tools::safeIntVal(substr($date, 0, 4));
        $month = Tools::safeIntVal(substr($date, 5, 2));
        $day = Tools::safeIntVal(substr($date, 8, 2));

        return mktime($hour, $minute, $second, $month, $day + $dayoffset, $year);
    }

    /**
     * Calculate the difference between two moments, in seconds.
     * @param string $realTimeTime in hh:mm:ss or dd:hh:mm:ss format
     * @param string $realTimeDate Date as a Y-m-d string.
     * @param string $plannedTime in hh:mm:ss or dd:hh:mm:ss format
     * @param string $plannedDate Date as a Y-m-d string.
     * @return int The difference between the two datetimes, in seconds.
     * @throws Exception
     */
    public static function calculateSecondsHHMMSS($realTimeTime, $realTimeDate, $plannedTime, $plannedDate)
    {
        return Tools::transformTime($realTimeTime, $realTimeDate) - Tools::transformTime($plannedTime, $plannedDate);
    }

    /**
     * This function transforms a duration in PT..H..M format into seconds.
     * @param string $duration
     * @return int Duration in seconds
     * @throws Exception
     */
    public static function transformDuration(string $duration)
    {
        $interval = new DateInterval($duration);
        return $interval->d * 24 * 3600 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    /**
     * This function transforms the b-rail formatted timestring and reformats it to seconds.
     * @param string $time in HHMMSS or DDHHMMSS format
     * @return int Duration in seconds
     */
    public static function transformDurationHHMMSS($time)
    {
        if (strlen($time) == 6) {
            $time = '00' . $time;
        }
        $days = intval(substr($time, 0, 2));
        $hour = intval(substr($time, 2, 2));
        $minute = intval(substr($time, 4, 2));
        $second = intval(substr($time, 6, 2));

        return $days * 86400 + $hour * 3600 + $minute * 60 + $second;
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
        return "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36";
    }

    /**
     * Send a HTTP response header to the requester, idicating that this response was served from an internal cache.
     * @param bool $cached
     */
    public static function sendIrailCacheResponseHeader(bool $cached) : void
    {
        header("X-iRail-cache-hit: " . $cached);
    }

    /**
     * Get the int value of a string by interpreting it as a decimal number. This prevents octal interpretation of numbers starting with a leading 0.
     * @param string $value
     * @return int
     */
    public static function safeIntVal(string $value): int
    {
        return intval(ltrim($value, '0')); // ltrim to avoid octal interpretation
    }

}
