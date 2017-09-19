<?php
use Cache\Namespaced\NamespacedCachePool;

/** Copyright (C) 2011 by iRail vzw/asbl
 * This is a class with static tools for you to use on the NMBS scraper. It contains stuff that is needed by all other classes.
 */
class tools
{

    /**
     * @var $cache Cache\Adapter\Common\AbstractCachePool cache pool which will be used throughout the application.
     */
    private static $cache;
    const cache_TTL = 15;

    /**
     * @param <type> $time -> in 00d15:24:00
     * @param <type> $date -> in 20100915
     * @return seconds since the Unix epoch
     */
    public static function transformTime($time, $date)
    {
        date_default_timezone_set('Europe/Brussels');
        $dayoffset = intval(substr($time, 0, 2));
        $hour = intval(substr($time, 3, 2));
        $minute = intval(substr($time, 6, 2));
        $second = intval(substr($time, 9, 2));
        $year = intval(substr($date, 0, 4));
        $month = intval(substr($date, 4, 2));
        $day = intval(substr($date, 6, 2));

        return mktime($hour, $minute, $second, $month, $day + $dayoffset, $year);
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
     * @return \Cache\Adapter\Common\AbstractCachePool the cachePool for this application
     */
    private static function createCachePool()
    {
        if (self::$cache == null) {
            // Try to use APC when available
            if (extension_loaded('apc')) {
                self::$cache = new \Cache\Adapter\Apcu\ApcuCachePool();
            } else {
                // Fall back to array cache
                self::$cache = new \Cache\Adapter\PHPArray\ArrayCachePool();
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
        self::createCachePool();

        try {
            if (self::$cache->hasItem($key)) {
                return self::$cache->getItem($key)->get();
            } else {
                return false;
            }
        } catch (Exception $e) {
            echo($e);
        }

        return false;
    }

    /**
     * Store an item in the cache
     *
     * @param String        $key   The key to store the object under
     * @param object|string $value The object to store
     */
    public static function setCachedObject($key, $value)
    {
        self::createCachePool();
        $item = self::$cache->getItem($key);

        $item->set($value);
        if (self::cache_TTL > 0) {
            $item->expiresAfter(self::cache_TTL);
        }

        self::$cache->save($item);
    }
}
