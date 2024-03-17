<?php

namespace Irail\Repositories\Nmbs\Traits;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateInterval;
use Exception;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Repositories\Nmbs\Tools\Tools;

trait TimeParser
{

    /**
     * @param string $date -> in Y-m-d
     * @param string $time -> in hh:mm:ss or dd:hh:mm:ss format
     * @return Carbon A carbon datetime representation of the parsed time.
     * @throws InternalProcessingException
     */
    public function parseDateAndTime(string $date, string $time): Carbon
    {
        if (strlen($time) != 8 && strlen($time) != 11) {
            throw new InternalProcessingException("Invalid time passed to transformTime, should be 8 or 11 digits! $time");
        }
        if (strlen($time) == 8) {
            $time = '00:' . $time;
        }

        $dayoffset = Tools::safeIntVal(substr($time, 0, 2));
        $hour = Tools::safeIntVal(substr($time, 3, 2));
        $minute = Tools::safeIntVal(substr($time, 6, 2));
        $second = Tools::safeIntVal(substr($time, 9, 2));

        $year = Tools::safeIntVal(substr($date, 0, 4));
        $month = Tools::safeIntVal(substr($date, 5, 2));
        $day = Tools::safeIntVal(substr($date, 8, 2));

        // Having issues with timezones? Check if APP_TIMEZONE is configured correctly
        // These timestamps are in local time, so include their timezone in the constructor
        return new Carbon(mktime($hour, $minute, $second, $month, $day + $dayoffset, $year), 'Europe/Brussels');
    }

    /**
     * This function transforms an ISO8601 duration in PT..H..M format into seconds.
     *
     * P is the duration designator (for period) placed at the start of the duration representation.
     * Y is the year designator that follows the value for the number of years.
     * M is the month designator that follows the value for the number of months.
     * W is the week designator that follows the value for the number of weeks.
     * D is the day designator that follows the value for the number of days.
     * T is the time designator that precedes the time components of the representation.
     *
     * @param string $duration
     * @return int The duration in seconds
     * @throws InternalProcessingException
     */
    protected function transformIso8601Duration(string $duration): int
    {
        try {
            return (int)CarbonInterval::instance(new DateInterval($duration))->totalSeconds;
        } catch (Exception $e) {
            Log::error("Failed to parse ISO 8601 duration from string '$duration'");
            throw new InternalProcessingException($e);
        }
    }

    /**
     * This function transforms the b-rail formatted timestring and reformats it to seconds.
     * @param string $time in HHMMSS or DDHHMMSS format
     * @return int Duration in seconds
     */
    protected function transformDurationHHMMSS($time)
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


    /**
     * @throws InternalProcessingException
     */
    private function getSecondsBetweenTwoDatesAndTimes(string $date, string $time, string $rtDate, string $rtTime): int
    {
        try {
            return $this->parseDateAndTime($date, $time)
                ->diffInSeconds($this->parseDateAndTime($rtDate, $rtTime), absolute: false);
        } catch (InternalProcessingException $e) {
            Log::error("Failed to parse duration '$date' '$time' '$rtDate' $rtTime': " . $e->getMessage());
            throw new InternalProcessingException($e);
        }
    }
}