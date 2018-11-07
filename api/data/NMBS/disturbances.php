<?php

include_once 'data/NMBS/tools.php';

class disturbances
{
    /**
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot($dataroot, $request)
    {
        $nmbsCacheKey = self::getNmbsCacheKey($request->getLang());
        $xml = tools::getCachedObject($nmbsCacheKey);

        try {
            if ($xml === false) {
                $xml = self::fetchData($request->getLang());
                tools::setCachedObject($nmbsCacheKey, $xml);
            }
            $data = self::parseData($xml);

            // Store a backup copy to deal with nmbs outages
            tools::setCachedObject(self::getNmbsCacheKeyLongStorage($request->getLang()), $data, 3600);
        } catch (Exception $exception) {
            $data = tools::getCachedObject(self::getNmbsCacheKeyLongStorage($request->getLang()));

            if ($data === false) {
                // No cached copy available
                throw $exception;
            }

            $disturbance = new stdClass();
            $disturbance->title = "Website issues";
            $disturbance->description = "It seems there are problems with the NMBS/SNCB website. Routeplanning or live data might not be available.";
            $disturbance->link = "https://belgianrail.be/";
            $disturbance->timestamp = round(microtime(true));
            array_unshift($data, $disturbance);
        }

        $dataroot->disturbance = $data;
    }

    public static function getNmbsCacheKey($lang)
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
    public static function getNmbsCacheKeyLongStorage($lang)
    {
        return 'NMBSDisturbances|' . $lang . '|backup';
    }

    /**
     * Retrieve the disturbances from the NMBS.
     *
     * @param string $lang The language in which the disturbances hsould be retrieved
     * @return string Broken XML
     */
    private static function fetchData($lang)
    {
        include '../includes/getUA.php';
        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => $irailAgent,
        ];

        $scrapeUrl = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/help.exe/" . strtolower($lang) . "?tpl=rss_feed";

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
     * @param string $xml The XML retrieved from the NMBS' broken RSS feed
     * @return array Array of StdClass objects containing the structured disturbance data
     */
    private static function parseData($xml)
    {
        // Clean XML. Their RSS XML is completely broken, so this step cannot be skipped!
        if (class_exists('tidy', false)) {
            $tidy = new tidy();
            $tidy->parseString($xml, ['input-xml' => true, 'output-xml' => true], 'utf8');
            $tidy->cleanRepair();
            $xml = $tidy->value;
        }

        $data = new SimpleXMLElement($xml);
        $disturbances = [];

        // Loop through all news items.
        foreach ($data->channel->item as $item) {
            $disturbance = new stdClass();

            // Each string has to be converted to force parsing the CDATA. Also trim any leading or trailing newlines.
            $disturbance->title = trim((String) $item->title, "\r\n ");
            $disturbance->description = trim((String) $item->description, "\r\n ");

            // Trim the description from any html
            $disturbance->description = str_replace('<br/>', "\n", $disturbance->description);

            if (strpos($disturbance->description,'<a href="http://www.belgianrail.be/jp/download/brail_him/') !== false){
                preg_match('/<a href="(?P<url>http:\/\/www.belgianrail.be\/jp\/download\/brail_him\/.*?)"/',$disturbance->description,$documentMatches);
                $disturbance->attachment = $documentMatches['url'];
                $disturbance->description = preg_replace('/<a href="http:\/\/www.belgianrail.be\/jp\/download\/brail_him\/.*?">.*?<\/a>/','',$disturbance->description);
            }

            $disturbance->description = trim((String) $item->description, "\r\n ");

            // This replaces a special character with a normal space, just to be sure
            $disturbance->description = str_replace(' ',' ',$disturbance->description);
            $disturbance->description = preg_replace('/<.*?>/', '', $disturbance->description);

            $disturbance->link = trim((String) $item->link, "\r\n ");

            $pubdate = $item->pubDate;
            $disturbance->timestamp = strtotime($pubdate);

            $disturbances[] = $disturbance;
        }

        return $disturbances;
    }
}
