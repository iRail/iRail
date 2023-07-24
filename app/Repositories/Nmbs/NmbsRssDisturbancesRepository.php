<?php

namespace Irail\Repositories\Nmbs;

use Carbon\Carbon;
use Exception;
use Irail\Http\Requests\ServiceAlertsRequest;
use Irail\Models\Message;
use Irail\Models\MessageLink;
use Irail\Models\MessageType;
use Irail\Models\Result\ServiceAlertsResult;
use Irail\Repositories\Nmbs\Tools\Tools;
use Irail\Repositories\ServiceAlertsRepository;
use Irail\Traits\Cache;
use SimpleXMLElement;
use tidy;

class NmbsRssDisturbancesRepository implements ServiceAlertsRepository
{

    use Cache;

    private array $readMoreStrings = [
        'nl' => 'Lees meer',
        'fr' => 'Lire plus',
        'en' => 'Read more',
        'de' => 'Weiterlesen',
    ];

    /**
     * @param ServiceAlertsRequest $request
     * @return ServiceAlertsResult
     * @throws Exception
     */
    public function getServiceAlerts(ServiceAlertsRequest $request): ServiceAlertsResult
    {
        $serviceAlertData = $this->getCacheWithDefaultCacheUpdate($request->getCacheId(), function () use ($request) {
            return $this->getFreshServiceAlerts($request);
        });
        return new ServiceAlertsResult($serviceAlertData->getValue());
    }

    /**
     * This is the entry point for the data fetching and transformation.
     * @param ServiceAlertsRequest $request
     * @return array
     * @throws Exception
     */
    public function getFreshServiceAlerts(ServiceAlertsRequest $request): array
    {

        try {
            $xmlData = $this->fetchData($request);
            $parsedData = $this->parseData($xmlData, $request);
            $this->setCachedObject($this->getNmbsCacheKeyLongStorage($request), $xmlData, 3600 * 6); // 6h backup cache
            return $parsedData;
        } catch (Exception $exception) {
            $backup = $this->getCacheEntry(self::getNmbsCacheKeyLongStorage($request));

            if ($backup === null) {
                // No cached copy available
                throw $exception;
            }

            $parsedData = self::parseData($backup->getValue(), $request);

            // This fallback ensures travellers get information if everything goes down.
            $disturbance = new Message(
                'irail.upstream.unavailable',
                $backup->getCreatedAt(),
                null,
                Carbon::now('Europe/Brussels'),
                MessageType::TROUBLE,
                'Website issues',
                'Routeplanning or live data might not be available.',
                'It seems there are problems with the NMBS/SNCB website. Routeplanning or live data might not be available. You are viewing the last available service alerts cached by iRail.',
                'iRail',
                [new MessageLink('https://belgianrail.be/', 'Open the NMBS website')]);
            array_unshift($parsedData, $disturbance);
            return $parsedData;
        }
    }

    /**
     * A second key, where we store results for 30 minutes. This way we can still provide data when the NMBS goes
     * offline.
     *
     * @param ServiceAlertsRequest $request
     * @return string
     */
    public function getNmbsCacheKeyLongStorage(ServiceAlertsRequest $request): string
    {
        return $request->getCacheId() . '|backup';
    }

    /**
     * Retrieve the disturbances from the NMBS.
     *
     * @param ServiceAlertsRequest $request
     * @return string Broken XML
     */
    private static function fetchData(ServiceAlertsRequest $request): string
    {
        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $scrapeUrl = 'https://www.belgianrail.be/jp/sncb-nmbs-routeplanner/help.exe/' . strtolower($request->getLanguage()) . '?tpl=rss_feed';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $scrapeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For some reason CURL can't verify the RSS SSL certificate
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Parse the RSS data from the NMBS.
     *
     * @param string               $xml The XML retrieved from the NMBS' broken RSS feed
     * @param ServiceAlertsRequest $request
     * @return array Array of StdClass objects containing the structured disturbance data
     * @throws Exception
     */
    private function parseData(string $xml, ServiceAlertsRequest $request): array
    {
        $data = $this->cleanAndParseXml($xml);
        $disturbances = [];
        // Loop through all news items.
        foreach ($data->channel->item as $item) {

            // Each string has to be converted to force parsing the CDATA. Also trim any leading or trailing newlines.
            $title = trim((string) $item->title, "\r\n ");
            $description = trim((string) $item->description, "\r\n ");
            $lead = explode('.', $description)[0];

            [$description, $links] = $this->extractLinks($description, $item, $request);
            $description = $this->cleanHtmlText($description, "<br>");
            $timestamp = new Carbon($item->pubDate);

            $type = MessageType::TROUBLE; // tplParamHimMsgInfoGroup=trouble
            if (str_contains($item->link, 'tplParamHimMsgInfoGroup=works')) {
                $type = MessageType::WORKS;
            }

            preg_match("/&messageID=(\d)+/", $item->link, $matches);
            $id = $matches[1];

            $disturbances[] = new Message(
                $id,
                $timestamp,
                null,
                $timestamp,
                $type,
                $title,
                $lead,
                $description,
                'NMBS/SNCB',
                $links
            );
        }

        return $disturbances;
    }

    /**
     * @param array|string|null $description
     * @param string            $newlineChar
     * @return string
     */
    public function cleanHtmlText(array|string|null $description, string $newlineChar): string
    {
        $newlinePlaceHolder = '%%NEWLINE%%'; // ensures we don't filter the end users placeholder, also safer regex testing

        // This replaces a special character with a normal space, just to be sure
        $description = str_replace(' ', ' ', $description);
        $description = preg_replace(
            '/<br ?\/><br ?\/>/',
            $newlinePlaceHolder,
            $description
        );
        $description = preg_replace(
            '/<br ?\/>/',
            $newlinePlaceHolder,
            $description
        );

        // Strip all html tags except anchor tags <a>
        $description = strip_tags($description, '<a>');
        $description = preg_replace('/\s+/', ' ', $description);
        // remove trailing newlines
        $description = preg_replace("/\s?$newlinePlaceHolder\s?$/", '', $description);
        // Replace the placeholder after stripping the HTML tags: the end user might want to use a <br> tag as placeholder
        $description = str_replace($newlinePlaceHolder, $newlineChar, $description);
        $description = trim($description, "\r\n ");
        return $description;
    }

    /**
     * @param string                $description
     * @param SimpleXMLElement|null $item
     * @param ServiceAlertsRequest  $request
     * @return array
     */
    public function extractLinks(string $description, ?SimpleXMLElement $item, ServiceAlertsRequest $request): array
    {
        $links = [];
        if (str_contains($description, '<a href="')) {
            preg_match_all(
                '/<a href="(?P<url>.*?)".*?>(?P<cta>.*?)<\/a>/',
                $description,
                $descriptionLinkMatches
            );
            for ($i = 0; $i < count($descriptionLinkMatches['url']); $i++) {
                $link = $descriptionLinkMatches['url'][$i];
                $linkText = $descriptionLinkMatches['cta'][$i];
                $links[] = new MessageLink($link, $linkText);
            }
            $description = preg_replace(
                '/<a href="http:\/\/www.belgianrail.be\/jp\/download\/brail_him\/.*?">.*?<\/a>/',
                '',
                $description
            );
        } else {
            $link = trim($item->link, "\r\n ");
            $linkText = $this->readMoreStrings[$request->getLanguage()];
            $links[] = new MessageLink($link, $linkText);
        }
        return [$description, $links];
    }

    /**
     * @param string $xml
     * @return SimpleXMLElement
     * @throws Exception
     */
    public function cleanAndParseXml(string $xml): SimpleXMLElement
    {
        $xml = str_replace('/>>', '/>', $xml); // Fix a common closing tag error before tidying

        // Clean XML. Their RSS XML is completely broken, so this step cannot be skipped!
        if (class_exists('tidy', false)) {
            $tidy = new tidy();
            $tidy->parseString($xml, ['input-xml' => true, 'output-xml' => true], 'utf8');
            $tidy->cleanRepair();
            $xml = $tidy->value;
        }

        libxml_use_internal_errors(); // Don't print XML errors
        $data = new SimpleXMLElement($xml);
        return $data;
    }
}
