<?php

namespace Irail\Util;

use DOMDocument;
use const LIBXML_HTML_NODEFDTD;
use const LIBXML_HTML_NOIMPLIED;

/**
 * An alternative to the PHP Tidy extension, which is not available on all platforms.
 */
class Tidy
{
    public static function repairXml(string $xml): string
    {
        if (empty($xml)) {
            return $xml;
        }
        $domDoc = new DOMDocument;
        $domDoc->recover = true;
        $domDoc->loadXML($xml, LIBXML_ERR_NONE);
        return $domDoc->saveXML();
    }

    /**
     * This method repairs HTML data, removing javascript while doing so.
     * @param string $xml
     * @return string
     */
    public static function repairHtmlRemoveJavascript(string $xml): string
    {
        // Removing scripts makes the cleanup process a lot easier
        $scriptFree = preg_replace('%<script.*?</script>%mis', '', $xml);
        $scriptAndStyleFree = preg_replace('%<style .*?</style>%mis', '', $scriptFree);
        $scriptAndStyleFree = str_replace('&nbsp;', ' ', $scriptAndStyleFree); // Remove non-breaking spaces
        // Remove carriage returns, as they otherwise will be encoded to &#13; and pollute the results
        $scriptAndStyleFree = str_replace("\r", '', $scriptAndStyleFree);
        // Encode incorrect encoded ampersands, easy case which doesn't need regex
        $scriptAndStyleFree = str_replace('& ', '&amp; ', $scriptAndStyleFree);
        // Encode incorrect encoded ampersands by checking if they are followed by a semicolon
        $scriptAndStyleFree = preg_replace('/&([^;]{5})/mis', '&amp;$1', $scriptAndStyleFree);

        if (empty($scriptAndStyleFree)) {
            return $scriptAndStyleFree;
        }

        $domDoc = new DOMDocument;
        $domDoc->recover = true;
        // Load without adding doctype, html, head and body tags
        $domDoc->loadHTML($scriptAndStyleFree, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        return $domDoc->saveXML();
    }
}