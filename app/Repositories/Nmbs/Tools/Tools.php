<?php

namespace Irail\Repositories\Nmbs\Tools;

/** Copyright (C) 2011 by iRail vzw/asbl
 * This is a class with static tools for you to use on the NMBS scraper. It contains stuff that is needed by all other classes.
 */
class Tools
{

    public static function getUserAgent(): string
    {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36';
    }

    /**
     * Send a HTTP response header to the requester, idicating that this response was served from an internal cache.
     * @param bool $cached
     */
    public static function sendIrailCacheResponseHeader(bool $cached) : void
    {
        header('X-iRail-cache-hit: ' . $cached);
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
