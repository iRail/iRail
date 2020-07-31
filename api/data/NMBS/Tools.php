<?php

use Cache\Adapter\Apcu\ApcuCachePool;
use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Namespaced\NamespacedCachePool;

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
     * @param string $time -> in 00d15:24:00 or hhmmss or ddhhmmss format
     * @param string $date -> in 20100915
     * @return int seconds since the Unix epoch
     */
    public static function transformTime($time, $date)
    {
        // Fixing inconsistent NMBS formatting. Again.
        if (strlen($time) == 6) {
            $time = '00' . $time;
        } else {
            $time = str_replace('d', '', $time);
        }
        $time = str_replace(':', '', $time);

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

    public static function calculateSecondsHHMMSS($realtime, $rtdate, $planned, $pdate)
    {
        return Tools::transformTime($realtime, $rtdate) - Tools::transformTime($planned, $pdate);
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
     * Adds a quarter and responds with a time.
     *
     * @param $time
     * @return string
     */
    public static function addQuarter($time)
    {
        preg_match('/(..):(..)/', $time, $m);
        $hours = $m[1];
        $minutes = $m[2];
        //echo $hours . " " . $minutes . "\n";
        if ($minutes >= 45) {
            $minutes = ($minutes + 15) - 60;
            if ($minutes < 10) {
                $minutes = '0' . $minutes;
            }
            $hours++;
            if ($hours > 23) {
                $hours = '00'; //no fallback for days?
            }
        } else {
            $minutes += 15;
        }

        return $hours . ':' . $minutes;
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
        } catch (\Psr\Cache\InvalidArgumentException $e) {
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
        } catch (\Psr\Cache\InvalidArgumentException $e) {
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
                    strrpos($vehicleId, '.') + 1
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
    public static function sendIrailCacheResponseHeader(bool $cached)
    {
        header("X-iRail-cache-hit: " . $cached);
    }
}
