<?php

namespace Irail\Data\Nmbs\Repositories;

use DateTime;
use Exception;
use Irail\Data\Nmbs\HafasDatasource;
use Irail\Models\CachedData;
use Irail\Models\DepartureArrivalMode;
use Irail\Models\Requests\LiveboardRequest;
use Irail\Models\Requests\VehicleJourneyRequest;
use Irail\Traits\Cache;

class RawDataRepository
{
    use Cache;
    use HafasDatasource;

    const HAFAS_MOBILE_API_ENDPOINT = 'http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/mgate.exe';
    const CURL_HEADER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36';
    const CURL_HEADER_REFERRER = 'http://api.irail.be/';
    const CURL_TIMEOUT = 30;
    const HAFAS_MGATE_CLIENT = [
        'id'   => 'SNCB',
        'name' => 'NMBS',
        'os'   => 'Android 5.0.2',
        'type' => 'AND',
        'ua'   => 'SNCB/302132 (Android_5.0.2) Dalvik/2.1.0 (Linux; U; Android 5.0.2; HTC One Build/LRX22G)',
        'v'    => 302132,
    ];
    const HAFAS_MGATE_AUTH = [
        'aid'  => 'sncb-mobi',
        'type' => 'AID',
    ];

    private StationsRepository $stationsRepository;

    /**
     * @param StationsRepository $stationsRepository
     */
    public function __construct(StationsRepository $stationsRepository)
    {
        $this->stationsRepository = $stationsRepository;
        $this->setCachePrefix('NMBS');
    }

    /**
     * @param LiveboardRequest $request
     * @return CachedData the data, along with information about its age and validity
     */
    public function getLiveboardData(LiveboardRequest $request): CachedData
    {
        return $this->getCacheWithDefaultCacheUpdate($request->getCacheId(), function () use ($request) {
            return $this->getFreshLiveboardData($request);
        });
    }

    /**
     * @param LiveboardRequest $request
     * @return bool|string
     */
    protected function getFreshLiveboardData(LiveboardRequest $request): string|bool
    {
        $hafasStationId = $this->iRailToHafasId($request->getStationId());

        $postdata = [
            'auth'      => self::HAFAS_MGATE_AUTH,
            'client'    => self::HAFAS_MGATE_CLIENT,
            'lang'      => $request->getLanguage(),
            'svcReqL'   =>
                [
                    0 =>
                        [
                            'cfg'  =>
                                [
                                    'polyEnc' => 'GPA',
                                ],
                            'meth' => 'StationBoard',
                            'req'  =>
                                [
                                    'date'     => $request->getDateTime()->format("Ymd"),
                                    'jnyFltrL' =>
                                        [
                                            0 =>
                                                [
                                                    'mode'  => 'BIT',
                                                    'type'  => 'PROD',
                                                    'value' => '1010111',
                                                ],
                                        ],
                                    'stbLoc'   =>
                                        [
                                            'lid'  => 'A=1@O=@U=80@L=00' . $hafasStationId . '@B=1',
                                            'type' => 'S',
                                        ],
                                    'time'     => $request->getDateTime()->format("Hms"),
                                    'maxJny'   => 50
                                ]
                        ]
                ],
            'ver'       => '1.21',
            'formatted' => false,
        ];
        if ($request->getDepartureArrivalMode() == DepartureArrivalMode::MODE_ARRIVAL) {
            $postdata['svcReqL'][0]['req']['type'] = 'ARR';
        }

        return $this->makePostRequestToNmbsMgate($postdata);
    }

    /**
     * Get data for a DatedVehicleJourney (also known as vehicle or trip, one vehicle making an A->B run)
     *
     * @param VehicleJourneyRequest              $request
     * @param \Irail\Data\Nmbs\VehicleDatasource $param
     * @return CachedData
     * @throws Exception
     */
    public function getVehicleJourneyData(VehicleJourneyRequest $request)
    {
        return $this->getCacheWithDefaultCacheUpdate($request->getCacheId(), function () use ($request) {
            return $this->getFreshVehicleJourneyData($request);
        });
    }

    /**
     * @param VehicleJourneyRequest $request
     * @return bool|string
     * @throws Exception
     */
    protected function getFreshVehicleJourneyData(VehicleJourneyRequest $request): string|bool
    {
        $journeyId = $request->getDatedJourneyId() != null
            ? $request->getDatedJourneyId()
            : $this->getJourneyIdForVehicleId(
                $request->getVehicleId(),
                $request->getDateTime(),
                $request->getLanguage()
            );
        return $this->getVehicleDataForJourneyId($journeyId, $request->getLanguage());
    }


    /**
     * @param        $jid
     * @param string $lang The preferred language for alerts and messages
     * @return bool|string
     */
    private function getVehicleDataForJourneyId($jid, string $lang)
    {
        $postdata = [
            'auth'      => self::HAFAS_MGATE_AUTH,
            'client'    => self::HAFAS_MGATE_CLIENT,
            'lang'      => $lang,
            'svcReqL'   =>
                [
                    0 =>
                        [
                            'cfg'  =>
                                [
                                    'polyEnc' => 'GPA',
                                ],
                            'meth' => 'JourneyDetails',
                            'req'  =>
                                [
                                    'jid' => $jid,
                                ]
                        ]
                ],
            'ver'       => '1.21',
            'formatted' => false,
        ];
        $response = $this->makePostRequestToNmbsMgate($postdata);
        // Store the raw output to a file on disk, for debug purposes
        if (key_exists('debug', $_GET) && isset($_GET['debug'])) {
            file_put_contents(
                '../storage/debug-vehicle-' . $jid . '-' . time() . '.log',
                $response
            );
        }
        return $response;
    }

    /**
     * @param string   $vehicleId
     * @param DateTime $date
     * @param string   $language
     * @return string Journey ID
     * @throws Exception
     */
    public function getJourneyIdForVehicleId(string $vehicleId, DateTime $date, string $language): string
    {
        // This data does not change, so cache it a couple of minutes instead of seconds.
        return $this->getCacheWithDefaultCacheUpdate("journeyIdLookup|$vehicleId|$language|" . $date->getTimestamp(), function () use ($vehicleId, $date, $language) {
            return $this->getFreshJourneyIdForVehicleId($vehicleId, $date, $language);
        }, 300)->getValue();
    }

    /**
     * @param string   $vehicleId
     * @param DateTime $date
     * @param string   $language
     * @return string Journey ID
     * @throws Exception
     */
    public function getFreshJourneyIdForVehicleId(string $vehicleId, DateTime $date, string $language): string
    {
        $postdata = [
            'auth'      => self::HAFAS_MGATE_AUTH,
            'client'    => self::HAFAS_MGATE_CLIENT,
            'lang'      => $language,
            'svcReqL'   =>
                [
                    0 =>
                        [
                            'cfg'  =>
                                [
                                    'polyEnc' => 'GPA',
                                ],
                            'meth' => 'JourneyMatch',
                            'req'  =>
                                [
                                    'date'     => $date->format('Ymd'),
                                    'input'    => $vehicleId,
                                    'jnyFltrL' => [
                                        [
                                            'mode'  => 'BIT',
                                            'type'  => 'PROD',
                                            'value' => '11101111000111',
                                        ]
                                    ],
                                ]
                        ]
                ],
            'ver'       => '1.21',
            'formatted' => false,
        ];

        $response = self::makePostRequestToNmbsMgate($postdata);
        if ($response === false) {
            throw new Exception("Failed to match vehicle id $vehicleId with a journey: invalid response from server", 503);
        }
        return $this->parseNmbsRawJourneyMatchResponse($response, $vehicleId);
    }

    /**
     * Parse a journeyMatch result, which contains a list of journeys with the vehicle short name,
     * origin and destination including departure and arrival times.
     *
     * Example match:
     * {
     *   "jid": "1|1|0|80|8082020",
     *   "date": "20200808",
     *   "prodX": 0,
     *   "stopL": [
     *     {
     *      "locX": 0,
     *      "dTimeS": "182900"
     *    },
     *    {
     *      "locX": 1,
     *      "aTimeS": "213500"
     *    }
     *  ],
     *  "sDaysL": [
     *    {
     *      "sDaysR": "not every day",
     *      "sDaysI": "11. Apr until 12. Dec 2020 Sa, Su; also 13. Apr, 1., 21. May, 1. Jun, 21. Jul, 11. Nov",
     *      "sDaysB": "000000000000000000000000000003860C383062C1C3060C183060D183060C183060C183060C183060C983060C10"
     *    }
     *   ]
     * }
     *
     * @param string $response
     * @param string $vehicleId
     * @return mixed
     * @throws Exception
     */
    private function parseNmbsRawJourneyMatchResponse(string $response, string $vehicleId): string
    {
        $json = json_decode($response, true);

        // Verify that the vehicle number matches with the query.
        // The best match should be on top, so we don't look further than the first response.
        try {
            $this->throwExceptionOnInvalidResponse($json);
        } catch (Exception $exception) {
            // An error in the journey id search should result in a 404, not a 500 error.
            throw new Exception("Vehicle not found", 404, $exception);
        }

        $vehicleDefinitions = $this->parseVehicleDefinitions($json);
        $vehicle = $vehicleDefinitions[$json['svcResL'][0]['res']['jnyL'][0]['prodX']];
        if (preg_match("/[A-Za-z]/", $vehicleId) != false) {
            // The search string contains letters, so we try to match train type and number (IC xxx)
            if (preg_replace("/[^A-Za-z0-9]/", "", $vehicle->getDisplayName()) !=
                preg_replace("/[^A-Za-z0-9]/", "", $vehicleId)) {
                throw new Exception("Vehicle $vehicleId not found", 404);
            }
        } else {
            // The search string contains no letters, so we try to match the train number (Train 538)
            if ($vehicleId != $vehicle->getNumber()) {
                throw new Exception("Vehicle number $vehicleId not found", 404);
            }
        }

        return $json['svcResL'][0]['res']['jnyL'][0]['jid'];
    }

    /**
     * @param string|array $postdata
     * @return bool|string
     */
    private function makePostRequestToNmbsMgate(string|array $postdata): string|false
    {
        if (is_array($postdata)) {
            $postdata = json_encode($postdata, JSON_UNESCAPED_SLASHES);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::HAFAS_MOBILE_API_ENDPOINT);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::CURL_HEADER_USER_AGENT);
        curl_setopt($ch, CURLOPT_REFERER, self::CURL_HEADER_REFERRER);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }


}
