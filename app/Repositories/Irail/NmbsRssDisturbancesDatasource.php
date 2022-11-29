<?php

namespace Irail\Repositories\Irail;

use Exception;
use Irail\api\data\DataRoot;
use Irail\Http\Requests\DisturbancesRequest;
use Irail\Repositories\Nmbs\Models\Disturbance;
use Irail\Repositories\Nmbs\Tools\Tools;
use SimpleXMLElement;
use stdClass;
use tidy;
use function Irail\Data\Nmbs\str_contains;

require_once __DIR__ . '/tools/Tools.php';

class NmbsRssDisturbancesDatasource
{
    const TYPE_DISTURBANCE = 'disturbance';
    const TYPE_PLANNED = 'planned';

    /**
     * This is the entry point for the data fetching and transformation.
     * @param DataRoot $dataroot
     * @param DisturbancesRequest $request
     * @throws Exception
     */
    public static function fillDataRoot(DataRoot $dataroot, DisturbancesRequest $request): void
    {
        $nmbsCacheKey = self::getNmbsCacheKey($request->getLang());
        $xml = Tools::getCachedObject($nmbsCacheKey);
        try {
            if ($xml === false) {
                $xml = self::fetchData($request->getLang());
                // short-term cache
                Tools::setCachedObject($nmbsCacheKey, $xml);
            } else {
                Tools::sendIrailCacheResponseHeader(true);
            }

            $data = self::parseData($xml, $request->getLinebreakCharacter());

            // Store a copy in a long-term cache to deal with nmbs outages. This is done after parsing to ensure we don't cache invalid data.
            Tools::setCachedObject(self::getNmbsCacheKeyLongStorage($request->getLang()), $xml, 3600);
        } catch (Exception $exception) {
            $xml = Tools::getCachedObject(self::getNmbsCacheKeyLongStorage($request->getLang()));

            if ($xml === false) {
                // No cached copy available
                throw $exception;
            }

            $data = self::parseData($xml, $request->getLinebreakCharacter());

            // This fallback ensures travellers get information if everything goes down.
            $disturbance = new stdClass();
            $disturbance->title = 'Website issues';
            $disturbance->description = 'It seems there are problems with the NMBS/SNCB website. Routeplanning or live data might not be available. You are viewing the last available disturbance data cached by iRail.';
            $disturbance->link = 'https://belgianrail.be/'; // Should this be a link to iRail instead?
            $disturbance->type = self::TYPE_DISTURBANCE;
            $disturbance->timestamp = round(microtime(true));
            array_unshift($data, $disturbance);
        }

        $dataroot->disturbance = $data;
    }

    /**
     * Get a key to identify this request in the in-memory cache. Note that this doesn't cache the iRail response, but the source data from the NMBS.
     * This way the cache is shared between XML and Json responses.
     *
     * @param string $lang
     * @return string
     */
    public static function getNmbsCacheKey(string $lang): string
    {
        return 'NMBSDisturbances|' . $lang;
    }

    /**
     * A second key, where we store results for 30 minutes. This way we can still provide data when the NMBS goes
     * offline.
     *
     * @param $lang
     * @return string
     */
    public static function getNmbsCacheKeyLongStorage($lang): string
    {
        return 'NMBSDisturbances|' . $lang . '|backup';
    }

    /**
     * Retrieve the disturbances from the NMBS.
     *
     * @param string $lang The language in which the disturbances hsould be retrieved
     * @return string Broken XML
     */
    private static function fetchData(string $lang): string
    {
        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $scrapeUrl = 'http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/help.exe/' . strtolower($lang) . '?tpl=rss_feed';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $scrapeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Parse the RSS data from the NMBS.
     *
     * @param string $xml The XML retrieved from the NMBS' broken RSS feed
     * @param string $newlineChar The character to use for newlines
     * @return array Array of StdClass objects containing the structured disturbance data
     */
    private static function parseData(string $xml, string $newlineChar): array
    {
        // Clean XML. Their RSS XML is completely broken, so this step cannot be skipped!
        if (class_exists('tidy', false)) {
            $tidy = new tidy();
            $tidy->parseString($xml, ['input-xml' => true, 'output-xml' => true], 'utf8');
            $tidy->cleanRepair();
            $xml = $tidy->value;
        }

        libxml_use_internal_errors(); // Don't print XML errors
        $data = new SimpleXMLElement($xml);
        $disturbances = [];
        // Loop through all news items.
        foreach ($data->channel->item as $item) {
            $disturbance = new Disturbance();

            // Each string has to be converted to force parsing the CDATA. Also trim any leading or trailing newlines.
            $disturbance->title = trim((string)$item->title, "\r\n ");
            $disturbance->description = trim((string)$item->description, "\r\n ");

            if (str_contains($disturbance->description, '<a href="http://www.belgianrail.be/jp/download/brail_him/')) {
                preg_match(
                    '/<a href="(?P<url>http:\/\/www.belgianrail.be\/jp\/download\/brail_him\/.*?)"/',
                    $disturbance->description,
                    $documentMatches
                );
                $disturbance->attachment = $documentMatches['url'];
                $disturbance->description = preg_replace(
                    '/<a href="http:\/\/www.belgianrail.be\/jp\/download\/brail_him\/.*?">.*?<\/a>/',
                    '',
                    $disturbance->description
                );
            }

            $disturbance->description = trim((string)$item->description, "\r\n ");

            $newlinePlaceHolder = '%%NEWLINE%%'; // ensures we don't filter the end users placeholder, also safer regex testing

            // This replaces a special character with a normal space, just to be sure
            $disturbance->description = str_replace(' ', ' ', $disturbance->description);
            $disturbance->description = preg_replace(
                '/<br ?\/><br ?\/>/',
                $newlinePlaceHolder,
                $disturbance->description
            );
            $disturbance->description = preg_replace(
                '/<br ?\/>/',
                $newlinePlaceHolder,
                $disturbance->description
            );

            // Strip all html tags except anchor tags <a>
            $disturbance->description = strip_tags($disturbance->description, '<a>');
            $disturbance->description = preg_replace('/\s+/', ' ', $disturbance->description);
            // remove trailing newlines
            $disturbance->description = preg_replace("/\s?$newlinePlaceHolder\s?$/", '', $disturbance->description);
            // Replace the placeholder after stripping the HTML tags: the end user might want to use a <br> tag as placeholder
            $disturbance->description = str_replace($newlinePlaceHolder, $newlineChar, $disturbance->description);
            $disturbance->description = trim($disturbance->description, "\r\n ");

            // This is the richtext version which can contain links etc
            $disturbance->richtext = $disturbance->description;

            // clean it further to a plaintext field
            $disturbance->description = preg_replace('/<a.*?>.*?<\/a>/', '', $disturbance->description);
            $disturbance->description = strip_tags($disturbance->description);
            // put the links from the description in an easy to use array for clients, so it can be used with the cleaned text
            $disturbance->descriptionLink = [];
            preg_match_all('/<a href="(.*?)">(.*?)<\/a>/', $disturbance->richtext, $descriptionLinkMatches);
            for ($i = 0; $i < count($descriptionLinkMatches[0]); $i++) {
                $link = new StdClass();
                $link->link = $descriptionLinkMatches[1][$i];
                $link->text = $descriptionLinkMatches[2][$i];
                $disturbance->descriptionLink[] = $link;
            }


            $disturbance->link = trim((string)$item->link, "\r\n ");

            $pubdate = $item->pubDate;
            $disturbance->timestamp = strtotime($pubdate);

            $disturbance->type = self::TYPE_DISTURBANCE;
            if (str_contains($disturbance->link, 'tplParamHimMsgInfoGroup=works')) {
                $disturbance->type = self::TYPE_PLANNED;
            }
            $disturbances[] = $disturbance;
        }

        return $disturbances;
    }
}
