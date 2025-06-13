<?php

namespace Irail\Repositories\Gtfs;

use Carbon\Carbon;

class GtfsRepository
{
    public static function secondsUntilGtfsCacheExpires(): int
    {
        return self::secondsUntilNextGtfsUpdate() + self::getGtfsBackgroundRefreshWindowSeconds();
    }


    /**
     * @return int The number of seconds between when a new GTFS file becomes available, and when the cache for the currently cached GTFS data expires. During
     * this window, cached data can be replaced by a cron job without causing a huge load spike.
     */
    public static function getGtfsBackgroundRefreshWindowSeconds(): int
    {
        // Keep stale GTFS data cached for an hour until it is refreshed
        return 3600;
    }

    public static function secondsUntilNextGtfsUpdate(): int
    {
        $now = Carbon::now()->timezone('Europe/Brussels');
        $gtfsReleaseTime = $now->copy()->timezone('Europe/Brussels')->setTime(06, 52);
        if ($gtfsReleaseTime->isBefore($now)) {
            $gtfsReleaseTime = $gtfsReleaseTime->addDay();
        }
        return $gtfsReleaseTime->diffInSeconds($now);
    }
}
