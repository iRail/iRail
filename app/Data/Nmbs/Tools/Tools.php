<?php

namespace Irail\Data\Nmbs\Tools;

use Cache\Adapter\Apcu\ApcuCachePool;
use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Namespaced\NamespacedCachePool;
use Exception;
use Irail\Data\Nmbs\Models\Station;
use Psr\Cache\InvalidArgumentException;

/** Copyright (C) 2011 by iRail vzw/asbl
 * This is a class with static tools for you to use on the NMBS scraper. It contains stuff that is needed by all other classes.
 */
class Tools
{

    /**
     * @var $cache Cache\Adapter\Common\AbstractCachePool cache pool which will be used throughout the application.
     */
    private static $cache;
    const cache_TTL = 15;
    const cache_prefix = "|Irail|Api|";

    /**
     * @param string $time -> in hhmmss or ddhhmmss format
     * @param string $date -> in 20100915
     * @return int seconds since the Unix epoch
     * @throws Exception
     */
    public static function transformTime($time, $date)
    {
        if (strlen($time) < 6) {
            throw new Exception("Invalid time passed to transformTime, should be 6 or 8 digits!");
        }
        if (strlen($time) == 6) {
            $time = '00' . $time;
        }

        date_default_timezone_set('Europe/Brussels');
        $dayoffset = intval(substr($time, 0, 2));
        $hour = intval(substr($time, 2, 2));
        $minute = intval(substr($time, 4, 2));
        $second = intval(substr($time, 6, 2));

        $year = intval(substr($date, 0, 4));
        $month = intval(substr($date, 4, 2));
        $day = intval(substr($date, 6, 2));

        return mktime($hour, $minute, $second, $month, $day + $dayoffset, $year);
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

    /**
     * This function transforms the brail formatted timestring and reformats it to seconds.
     * @param int $time
     * @return int Duration in seconds
     */
    public static function transformDuration($time)
    {
        $days = intval(substr($time, 0, 2));
        $hour = intval(substr($time, 3, 2));
        $minute = intval(substr($time, 6, 2));
        $second = intval(substr($time, 9, 2));

        return $days * 24 * 3600 + $hour * 3600 + $minute * 60 + $second;
    }

    /**
     * This function transforms the brail formatted timestring and reformats it to seconds.
     * @param int $time
     * @return int Duration in seconds
     */
    public static function transformDurationHHMMSS($time)
    {
        $hour = intval(substr($time, 0, 2));
        $minute = intval(substr($time, 2, 2));
        $second = intval(substr($time, 4, 2));

        return $hour * 3600 + $minute * 60 + $second;
    }

    /**
     * @return AbstractCachePool the cachePool for this application
     */
    private static function createCachePool()
    {
        if (self::$cache == null) {
            // Try to use APC when available
            if (extension_loaded('apcu')) {
                self::$cache = new ApcuCachePool();
            } else {
                // Fall back to array cache
                self::$cache = new ArrayCachePool();
            }
        }

        return self::$cache;
    }


    /**
     * Get an item from the cache.
     *
     * @param String $key The key to search for.
     * @return bool|object The cached object if found. If not found, false.
     */
    public static function getCachedObject($key)
    {
        $key = self::cache_prefix . $key;

        self::createCachePool();

        try {
            if (self::$cache->hasItem($key)) {
                return self::$cache->getItem($key)->get();
            } else {
                return false;
            }
        } catch (InvalidArgumentException $e) {
            // Todo: log something here
            return false;
        }
    }

    /**
     * Store an item in the cache
     *
     * @param String $key The key to store the object under
     * @param object|array|string $value The object to store
     * @param int $ttl The number of seconds to keep this in cache
     */
    public static function setCachedObject($key, $value, $ttl = self::cache_TTL)
    {
        $key = self::cache_prefix . $key;

        self::createCachePool();
        try {
            $item = self::$cache->getItem($key);
        } catch (InvalidArgumentException $e) {
            // Todo: log something here
            return;
        }

        $item->set($value);
        if ($ttl > 0) {
            $item->expiresAfter($ttl);
        }

        self::$cache->save($item);
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
