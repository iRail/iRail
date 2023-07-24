<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * fillDataRoot will fill the entire dataroot with a liveboard for a specific station.
 */

namespace Irail\Repositories\Nmbs;

use Carbon\Carbon;
use DateTime;
use Exception;
use Irail\api\data\DataRoot;
use Irail\api\data\models\DepartureArrival;
use Irail\api\data\models\Platform;
use Irail\api\data\models\Station;
use Irail\api\data\models\VehicleInfo;
use Irail\api\data\NMBS\StationsDatasource;
use Irail\api\data\NMBS\tools\Tools;
use Irail\api\requests\LiveboardRequest;
use SimpleXMLElement;
use tidy;

class NmbsHtmlLiveboardRepository
{
    /**
     * This is the entry point for the data fetching and transformation.
     *
     * @param DataRoot         $dataroot
     * @param LiveboardRequest $request
     * @throws Exception
     */
    public static function fillDataRoot(DataRoot $dataroot, LiveboardRequest $request): void
    {
        if (strtoupper(substr($request->getArrdep(), 0, 1)) == 'A') {
            self::fillDataRootWithArrivalData($dataroot, $request);
        } else {
            if (strtoupper(substr($request->getArrdep(), 0, 1)) == 'D') {
                self::FillDataRootWithDepartureData($dataroot, $request);
            } else {
                throw new Exception('Not a good timeSel value: try \'arrival\' or \'departure\'', 400);
            }
        }
    }

    /**
     * @param DataRoot         $dataroot
     * @param LiveboardRequest $request
     * @throws Exception
     */
    private static function fillDataRootWithArrivalData(DataRoot $dataroot, LiveboardRequest $request): void
    {
        $nmbsCacheKey = self::getNmbsCacheKey(
            $dataroot->station,
            $request->getTime(),
            $request->getDate(),
            $request->getLang(),
            'arr'
        );
        $html = Tools::getCachedObject($nmbsCacheKey);

        if ($html === false) {
            $html = self::fetchDataFromNmbs(
                $dataroot->station,
                $request->getTime(),
                $request->getDate(),
                $request->getLang(),
                'arr'
            );

            if (empty($html)) {
                throw new Exception('No response from NMBS/SNCB', 504);
            }

            Tools::setCachedObject($nmbsCacheKey, $html);
        } else {
            Tools::sendIrailCacheResponseHeader(true);
        }

        $dataroot->arrival = self::parseNmbsData($html, $dataroot->station, $request);
    }

    /**
     * @param DataRoot         $dataroot
     * @param LiveboardRequest $request
     * @throws Exception
     */
    private static function FillDataRootWithDepartureData(DataRoot $dataroot, LiveboardRequest $request): void
    {
        $nmbsCacheKey = self::getNmbsCacheKey(
            $dataroot->station,
            $request->getTime(),
            $request->getDate(),
            $request->getLang(),
            'dep'
        );
        $html = Tools::getCachedObject($nmbsCacheKey);

        if ($html === false) {
            $html = self::fetchDataFromNmbs(
                $dataroot->station,
                $request->getTime(),
                $request->getDate(),
                $request->getLang(),
                'dep'
            );
            Tools::setCachedObject($nmbsCacheKey, $html, 20);
        } else {
            Tools::sendIrailCacheResponseHeader(true);
        }

        $dataroot->departure = self::parseNmbsData($html, $dataroot->station, $request);
    }

    /**
     * Get a unique key to identify data in the in-memory cache which reduces the number of requests to the NMBS.
     * @param Station $station
     * @param string  $time
     * @param string  $date
     * @param string  $lang
     * @param string  $timeSel
     * @return string
     */
    public static function getNmbsCacheKey(Station $station, string $time, string $date, string $lang, string $timeSel): string
    {
        return 'NMBSLiveboard|fallback|' . join('.', [
                $station->id,
                str_replace(':', '.', $time),
                $date,
                $timeSel,
                $lang,
            ]);
    }

    /**
     * Fetch JSON data from the NMBS.
     *
     * @param Station $station
     * @param string  $time Time in hh:mm format
     * @param string  $date Date in YYYYmmdd format
     * @param string  $lang
     * @param string  $timeSel
     * @return string
     */
    private static function fetchDataFromNmbs(Station $station, string $time, string $date, string $lang, string $timeSel): string
    {
        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $url = 'http://www.belgianrail.be/jp/nmbs-realtime/stboard.exe/nn';
        $dateTime = DateTime::createFromFormat('Ymd H:i', $date . ' ' . $time);
        $formattedDateStr = $dateTime->format('d/m/Y');
        $formattedTimeStr = $dateTime->format('H:i:s');

        $parameters = [
            'ld'                      => 'std',
            'boardType'               => $timeSel,
            'time'                    => $formattedTimeStr,
            'date'                    => $formattedDateStr,
            'maxJourneys'             => 50,
            'wDayExtsq'               => 'Ma|Di|Wo|Do|Vr|Za|Zo',
            'input'                   => $station->name,
            'inputRef'                => $station->name . '#' . substr($station->_hafasId, 2),
            'REQ0JourneyStopsinputID' => "A=1@O={$station->name}@X=4356802@Y=50845649@U=80@L={$station->_hafasId}@B=1@p=1669420371@n=ac.1=FA@n=ac.2=LA@n=ac.3=FS@n=ac.4=LS@n=ac.5=GA@",
            'REQProduct_list'         => '5:1111111000000000',
            'realtimeMode'            => 'show',
            'start'                   => 'yes'
            // language is not passed, since we need to parse the resulting webpage
        ];

        $url = $url . '?' . http_build_query($parameters, '', null,);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);

        $response = curl_exec($ch);
        curl_close($ch);

        $matches = [];

        if (str_contains($response, 'vallen niet binnen de dienstregelingsperiode')) {
            throw new Exception('The data for which you requested data is too far in the past or future.', 404);
        }

        // Only keep the actual data, php tidy can't handle the entire document
        preg_match('/<table class="resultTable" cellspacing="0">.*?<\/table>/s', $response, $matches);
        $response = $matches[0];

        // Store the raw output to a file on disk, for debug purposes
        if (key_exists('debug', $_GET) && isset($_GET['debug'])) {
            file_put_contents(
                '../../storage/debug-liveboard-fallback-' . $station->_hafasId . '-' .
                time() . '.html',
                $response
            );
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    private static function parseNmbsData($xml, Station $station, LiveboardRequest $request)
    {
        //clean XML
        if (class_exists('tidy', false)) {
            $tidy = new tidy();
            $tidy->parseString($xml, ['input-xml' => true, 'output-xml' => true], 'utf8');
            $tidy->cleanRepair();
        } else {
            throw new Exception('PHP Tidy is required to clean the data sources.', 500);
        }

        $data = new SimpleXMLElement($tidy);

        $stBoardEntries = $data->xpath("tr[@class='stboard']");
        // <tr class="stboard">
        //<td class="time">
        //18:23
        //<span class="prognosis">
        //<span class="delay prognosis">+3 min.</span>
        //</span>
        //</td>
        //<td>
        //<a href="http://www.belgianrail.be/Station.ashx?lang=fr&amp;stationId=8819406">
        //Brussels Airport - Zaventem
        //</a>
        //</td>
        //<td class="product">
        //<a onclick="loadDetails('http://www.belgianrail.be/jp/nmbs-realtime/traininfo.exe/fn/729576/246586/687502/100559/80?ld=std&amp;AjaxMap=CPTVMap&amp;date=26/11/2022&station_evaId=8814001&station_type=dep&amp;input=8814001&amp;boardType=dep&amp;time=18:23&amp;maxJourneys=50&amp;dateBegin=26/11/2022&amp;dateEnd=26/11/2022&amp;selectDate=&amp;dirInput=&amp;backLink=sq&amp;ajax=1&amp;divid=updatejourney_0','updatejourney_0','rowjourney_0'); return false;" href="http://www.belgianrail.be/jp/nmbs-realtime/traininfo.exe/fn/729576/246586/687502/100559/80?ld=std&amp;AjaxMap=CPTVMap&amp;date=26/11/2022&station_evaId=8814001&station_type=dep&amp;input=8814001&amp;boardType=dep&amp;time=18:23&amp;maxJourneys=50&amp;dateBegin=26/11/2022&amp;dateEnd=26/11/2022&amp;selectDate=&amp;productsFilter=5:1111111&amp;dirInput=&amp;backLink=sq&amp;"><img src="/jp/hafas-res/img/products/ic.png" alt="IC  2315" /> IC  2315</a>
        //</td>
        //<td class="platform">
        //<div class="relative">
        //<span class="prognosis" onmouseover="openInfoLayer('journey_0_remarks_plChangeDep');"  onmouseout="closeInfoLayer('journey_0_remarks_plChangeDep');">
        //12  <img src="/jp/hafas-res/img/icon_i.gif" alt="" style="vertical-align: middle;" /><br />
        //</span>
        //<div id="journey_0_remarks_plChangeDep" class="dtlMessages hidden overlay" >
        //<div class="journeyMessageHIM">
        //<img src="/jp/hafas-res/img/icon_critical.gif" />
        //Veuillez noter le changement de voie!
        //</div>
        //</div>
        //</div>
        //&nbsp;
        //</td>
        //<td class="remarks last">
        //</td>
        //</tr>

        $departureArrivals = [];
        $previousTimeWasDualDigit = false;
        $daysForward = 0;
        foreach ($stBoardEntries as $stBoardEntry) {
            $canceled = false;

            $delay = $stBoardEntry->xpath("td[@class='time']/span[@class='prognosis']/text()");

            if (empty($delay)) {
                $delay = 0;
            } else if ($delay[0] == 'Cancelled') {
                $delay = 0;
                $canceled = true;
            } else {
                $delay = (int)explode(' ', trim($delay[0]))[0];
            }

            $platformChangeInformation = $stBoardEntry->xpath("td[@class='platform']/div[@class='relative']/td[@class='prognosis']");
            $platformInformation = $stBoardEntry->xpath("td[@class='platform']/text()");
            if (!empty($platformInformation)) {
                $platform = trim((string)$platformInformation[0]);
                $platform = trim(explode("\n", $platform)[0]); // remove a newline and some garbage characters
                $platform = str_replace("\xc2\xa0", '', $platform);// Remove non-breaking spaces
            } else {
                $platform = '?';
            }

            $platformNormal = true;
            if (!empty($platformChangeInformation)) {
                $platformNormal = false;
                $platform = trim((string)$platformChangeInformation[0]);
            }
            if (empty($platform)) {
                $platform = '?';
            }

            $time = trim((string)$stBoardEntry->xpath("td[@class='time']")[0]);
            $time = explode(' ', $time)[0];
            $thisTimeIsDualDigit = !str_starts_with($time, '0');

            $date = Carbon::createFromFormat('Ymd', $request->getDate());
            if (!$thisTimeIsDualDigit && $previousTimeWasDualDigit) {
                $daysForward++;
            }
            $date->addDays($daysForward);
            $previousTimeWasDualDigit = $thisTimeIsDualDigit; // After 10 in the morning, if we find time after midnight before 10 now we crossed a date line!

            $unixtime = Tools::transformtime($time . ':00', $date->format('Y-m-d'));

            // http://www.belgianrail.be/Station.ashx?lang=en&stationId=8885001
            if (isset($stBoardEntry->xpath('td')[1]->a['href'])) {
                $directionHafasId = '00' . substr($stBoardEntry->xpath('td')[1]->a['href'], 57);
                $direction = StationsDatasource::getStationFromID($directionHafasId, $request->getLang());
            } else {
                $directionName = $stBoardEntry->xpath('td')[1];
                $direction = StationsDatasource::getStationFromName($directionName, $request->getLang());
            }
            $vehicleTypeAndNumber = trim((string)$stBoardEntry->xpath("td[@class='product']/a")[0]);
            // Some trains are split by a newline, some by a space
            $vehicleTypeAndNumber = str_replace("\n", ' ', $vehicleTypeAndNumber);
            // Busses are completely missing a space, TRN trains are missing this sometimes
            $vehicleTypeAndNumber = str_replace('BUS', 'BUS ', $vehicleTypeAndNumber);
            $vehicleTypeAndNumber = str_replace('TRN', 'TRN ', $vehicleTypeAndNumber);
            // Replace possible double spaces after the space-introducing fixes
            $vehicleTypeAndNumber = str_replace('  ', ' ', $vehicleTypeAndNumber);

            $vehicleTypeAndNumber = explode(' ', $vehicleTypeAndNumber);
            $vehicle = new VehicleInfo($vehicleTypeAndNumber[0], $vehicleTypeAndNumber[1]);

            $stopAtStation = new DepartureArrival();
            $stopAtStation->delay = $delay;
            $stopAtStation->station = $direction;
            $stopAtStation->time = $unixtime;
            $stopAtStation->vehicle = $vehicle;
            $stopAtStation->platform = new Platform($platform, $platformNormal);
            $stopAtStation->canceled = $canceled;

            $stopAtStation->left = 0; // Not available from this data source
            $stopAtStation->isExtra = 0; // Not available from this data source
            $stopAtStation->departureConnection = 'http://irail.be/connections/' . substr(
                    basename($station->{'@id'}),
                    2
                ) . '/' . date('Ymd', $unixtime) . '/' . $vehicle->shortname;

            $departureArrivals[] = $stopAtStation;
        }

        return array_merge($departureArrivals); //array merge reindexes the array
    }
}
