<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * fillDataRoot will fill the entire dataroot with a liveboard for a specific station.
 */

namespace Irail\Repositories\Nmbs;

use Carbon\Carbon;
use Exception;
use Irail\Exceptions\Request\RequestOutsideTimetableRangeException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\CachedData;
use Irail\Models\DepartureOrArrival;
use Irail\Models\PlatformInfo;
use Irail\Models\Result\LiveboardSearchResult;
use Irail\Models\Station;
use Irail\Models\Vehicle;
use Irail\Models\VehicleDirection;
use Irail\Proxy\CurlHttpResponse;
use Irail\Proxy\CurlProxy;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\LiveboardRepository;
use Irail\Traits\Cache;
use Irail\Util\Tidy;
use SimpleXMLElement;

class NmbsHtmlLiveboardRepository implements LiveboardRepository
{
    use Cache;

    private StationsRepository $stationsRepository;
    private CurlProxy $curlProxy;
    private GtfsTripStartEndExtractor $gtfsTripStartEndExtractor;

    /**
     * @param StationsRepository        $stationsRepository
     * @param CurlProxy                 $curlProxy
     * @param GtfsTripStartEndExtractor $gtfsTripStartEndExtractor Used to find the departure date for trips
     */
    public function __construct(
        StationsRepository $stationsRepository,
        CurlProxy $curlProxy,
        GtfsTripStartEndExtractor $gtfsTripStartEndExtractor
    ) {
        $this->stationsRepository = $stationsRepository;
        $this->curlProxy = $curlProxy;
        $this->gtfsTripStartEndExtractor = $gtfsTripStartEndExtractor;
        $this->setCachePrefix('NMBS');
    }


    /**
     * This is the entry point for the data fetching and transformation.
     *
     * @param LiveboardRequest $request
     * @return LiveboardSearchResult
     * @throws Exception
     */
    public function getLiveboard(LiveboardRequest $request): LiveboardSearchResult
    {
        $station = $this->stationsRepository->getStationById($request->getStationId());

        $rawData = $this->getLiveboardHtml($request, $station);
        $entries = $this->parseNmbsData($request, $station, $rawData->getValue());
        return new LiveboardSearchResult($station, $entries);
    }

    private function getLiveboardHtml(LiveboardRequest $request, Station $station): CachedData
    {
        return $this->getCacheOrUpdate($request->getCacheId(), function () use ($request, $station) {
            return $this->fetchLiveboardHtml($request, $station);
        }, 60);
    }

    /**
     * Fetch JSON data from the NMBS.
     *
     * @param LiveboardRequest $request
     * @param Station          $station
     * @return string
     */
    private function fetchLiveboardHtml(LiveboardRequest $request, Station $station): string
    {
        $url = 'http://www.belgianrail.be/jp/nmbs-realtime/stboard.exe/nn';
        $formattedDateStr = $request->getDateTime()->format('d/m/Y');
        $formattedTimeStr = $request->getDateTime()->format('H:i:s');

        $parameters = [
            'ld'                      => 'std',
            'boardType'               => $request->getDepartureArrivalMode() == TimeSelection::DEPARTURE ? 'dep' : 'arr',
            'time'                    => $formattedTimeStr,
            'date'                    => $formattedDateStr,
            'maxJourneys'             => 50,
            'wDayExtsq'               => 'Ma|Di|Wo|Do|Vr|Za|Zo',
            'input'                   => $station->getStationName(),
            'inputRef'                => $station->getStationName() . '#' . substr($station->getId(), 2),
            'REQ0JourneyStopsinputID' => "A=1@O={$station->getStationName()}@X=4356802@Y=50845649@U=80@L={$station->getId()}@B=1@p=1669420371@n=ac.1=FA@n=ac.2=LA@n=ac.3=FS@n=ac.4=LS@n=ac.5=GA@",
            'REQProduct_list'         => '5:1111111000000000',
            'realtimeMode'            => 'show',
            'start'                   => 'yes'
            // language is not passed, since we need to parse the resulting webpage
        ];
        $response = $this->curlProxy->get($url, $parameters);

        if ($response->getResponseCode() != 200) {
            throw new UpstreamServerException('Failed to fetch data from the NMBS website: ' . $response->getResponseCode());
        }

        if (str_contains($response->getResponseBody(), 'vallen niet binnen de dienstregelingsperiode')) {
            throw new RequestOutsideTimetableRangeException('The data for which you requested data is too far in the past or future.', 404);
        }
        return $this->cleanResponse($response);
    }

    /**
     * @param CurlHttpResponse $response
     * @return string The cleaned, simplified response data
     */
    public function cleanResponse(CurlHttpResponse $response): string
    {
        // Only keep the actual data, so we don't need to try and repair the entire response
        $matches = [];
        preg_match('/<table class="resultTable" cellspacing="0">.*?<\/table>/s', $response->getResponseBody(), $matches);
        $response = $matches[0];

        $response = preg_replace('/<tr class="sqLinkRow.*?<\/tr>/s', '', $response);
        $response = preg_replace('/onclick="loadDetails(.*?)"/s', '', $response);
        return $response;
    }

    /**
     * @return DepartureOrArrival[]
     * @throws Exception
     */
    private function parseNmbsData(LiveboardRequest $request, Station $station, string $xml): array
    {
        //clean XML
        $xml = Tidy::repairHtmlRemoveJavascript($xml);

        $data = new SimpleXMLElement($xml);

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
        $previousTimeWasDualDigit = $request->getDateTime()->hour > 9;
        $daysForward = 0;
        foreach ($stBoardEntries as $stBoardEntry) {
            $canceled = false;

            $delay = $stBoardEntry->xpath("td[@class='time']/span[@class='prognosis']/text()");

            if (empty($delay)) {
                $delay = 0;
            } elseif ($delay[0] == 'Cancelled') {
                $delay = 0;
                $canceled = true;
            } else {
                $delay = (int)explode(' ', trim($delay[0]))[0];
            }

            $platformChangeInformation = $stBoardEntry->xpath("td[@class='platform']/div[@class='relative']/span[@class='prognosis']");
            $platformInformation = $stBoardEntry->xpath("td[@class='platform']/text()");
            if (!empty($platformInformation)) {
                $platform = self::trim($platformInformation[0]);
                $platform = self::trim(explode("\n", $platform)[0]); // remove a newline and some garbage characters
                $platform = str_replace("\xc2\xa0", '', $platform);// Remove non-breaking spaces
            } else {
                $platform = '?';
            }

            $platformNormal = true;
            if (!empty($platformChangeInformation)) {
                $platformNormal = false;
                $platform = self::trim($platformChangeInformation[0]);
            }
            if (empty($platform)) {
                $platform = '?';
            }

            $time = self::trim($stBoardEntry->xpath("td[@class='time']")[0]);
            $time = explode(' ', $time)[0];
            $thisTimeIsDualDigit = !str_starts_with($time, '0');
            if (!$thisTimeIsDualDigit && $previousTimeWasDualDigit) {
                $daysForward++;
            }
            $previousTimeWasDualDigit = $thisTimeIsDualDigit; // After 10 in the morning, if we find time after midnight before 10 now we crossed a date line!

            $dateTime = Carbon::createFromFormat('Ymd H:i', $request->getDateTime()->format('Ymd') . ' ' . $time);
            $dateTime->addDays($daysForward);

            $vehicleDirection = $this->parseDirection($stBoardEntry);
            $vehicle = $this->parseVehicle($stBoardEntry, $dateTime);
            $vehicle->setDirection($vehicleDirection);

            $stopAtStation = new DepartureOrArrival();
            $stopAtStation->setDelay($delay);
            $stopAtStation->setStation($station);
            $stopAtStation->setScheduledDateTime($dateTime);
            $stopAtStation->setVehicle($vehicle);
            $stopAtStation->setPlatform(new PlatformInfo($station->getId(), $platform, !$platformNormal));
            $stopAtStation->setIsCancelled($canceled);

            $stopAtStation->setIsExtra(false); // Not available from this data source
            $stopAtStation->setIsReported(false); // Not available from this data source

            $departureArrivals[] = $stopAtStation;
        }

        return array_merge($departureArrivals); //array merge reindexes the array
    }

    /**
     * @param mixed  $stBoardEntry
     * @param Carbon $scheduledStopTimeForVehicle Used to determine the start time for the vehicle
     * @return Vehicle
     */
    public function parseVehicle(mixed $stBoardEntry, Carbon $scheduledStopTimeForVehicle): Vehicle
    {
        $vehicleTypeAndNumber = self::trim($stBoardEntry->xpath("td[@class='product']/a")[0]);
        // Some trains are split by a newline, some by a space
        $vehicleTypeAndNumber = str_replace("\n", ' ', $vehicleTypeAndNumber);
        // Busses are completely missing a space, TRN trains are missing this sometimes
        $vehicleTypeAndNumber = str_replace('BUS', 'BUS ', $vehicleTypeAndNumber);
        $vehicleTypeAndNumber = str_replace('TRN', 'TRN ', $vehicleTypeAndNumber);
        // Replace possible double spaces after the space-introducing fixes
        $vehicleTypeAndNumber = str_replace('  ', ' ', $vehicleTypeAndNumber);

        preg_match('/^(\w+?)\s*(\d+)$/', $vehicleTypeAndNumber, $vehicleTypeAndNumberArray);
        $journeyNumber = $vehicleTypeAndNumberArray[2];
        $journeyType = $vehicleTypeAndNumberArray[1];
        $journeyStartDate = $this->gtfsTripStartEndExtractor->getStartDate($journeyNumber, $scheduledStopTimeForVehicle) ?: $scheduledStopTimeForVehicle;
        return Vehicle::fromTypeAndNumber($journeyType, $journeyNumber, $journeyStartDate);
    }

    /**
     * @param mixed $stBoardEntry
     * @return VehicleDirection
     */
    public function parseDirection(mixed $stBoardEntry): VehicleDirection
    {
        // http://www.belgianrail.be/Station.ashx?lang=en&stationId=8885001
        if (isset($stBoardEntry->xpath('td')[1]->a['href'])) {
            $directionName = self::trim($stBoardEntry->xpath('td')[1]->a);
            $directionHafasId = '00' . substr($stBoardEntry->xpath('td')[1]->a['href'], 57);
            $directionStation = $this->stationsRepository->getStationById($directionHafasId);
        } else {
            $directionName = self::trim($stBoardEntry->xpath('td')[1]);
            $directionStation = $this->stationsRepository->findStationByName($directionName);
        }
        return new VehicleDirection($directionName, $directionStation);
    }

    private static function trim(string $str): string
    {
        return trim($str, "\ \n\r\t\v\0");
    }
}
