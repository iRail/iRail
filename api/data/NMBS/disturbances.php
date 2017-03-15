<?php

class disturbances
{
    /**
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot($dataroot, $request)
    {
        $xml = self::fetchData($request->getLang());
        $data = self::parseData($xml);
        $dataroot->disturbances = $data;
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
            $disturbance->title = trim((String) $item->title,"\r\n ");
            $disturbance->description = trim((String) $item->description,"\r\n ");

            // Trim the description from any html
            $disturbance->description = preg_replace('/<.*?>/','',$disturbance->description);

            $disturbance->link = trim((String) $item->link,"\r\n ");

            $pubdate = $item->pubDate;
            $disturbance->timestamp = strtotime($pubdate);

            $disturbances[] = $disturbance;
        }

        return $disturbances;
    }
}