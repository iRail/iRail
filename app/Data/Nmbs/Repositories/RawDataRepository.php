<?php

namespace Irail\Data\Nmbs\Repositories;

use Irail\Data\Nmbs\Tools\Tools;
use Irail\Models\Result\DepartureArrivalMode;
use Irail\Models\Result\Request\LiveboardRequest;
use Irail\Traits\Cache;

class RawDataRepository
{
    use Cache;

    private StationsRepository $stationsRepository;

    /**
     * @param StationsRepository $stationsRepository
     */
    public function __construct(StationsRepository $stationsRepository)
    {
        $this->stationsRepository = $stationsRepository;
    }

    public function getLiveboardData(LiveboardRequest $request): string
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
        $iRailStation = $this->stationsRepository->getStationById($request->getStationId());

        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $url = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/mgate.exe";

        $postdata = [
            'auth'      =>
                [
                    'aid'  => 'sncb-mobi',
                    'type' => 'AID',
                ],
            'client'    =>
                [
                    'id'   => 'SNCB',
                    'name' => 'NMBS',
                    'os'   => 'Android 5.0.2',
                    'type' => 'AND',
                    'ua'   => 'SNCB/302132 (Android_5.0.2) Dalvik/2.1.0 (Linux; U; Android 5.0.2; HTC One Build/LRX22G)',
                    'v'    => 302132,
                ],
            'lang'      => $request->getIso2Language(),
            'svcReqL'   =>
                [
                    0 =>
                        [
                            'cfg'  =>
                                [
                                    'polyEnc' => 'GPA',
                                ],
                            'meth' => 'StationBoardResult',
                            'req'  =>
                                [
                                    'date'                => $request->getDateTime()->format("Ymd"),
                                    'jnyFltrL'            =>
                                        [
                                            0 =>
                                                [
                                                    'mode'  => 'BIT',
                                                    'type'  => 'PROD',
                                                    'value' => '1010111',
                                                ],
                                        ],
                                    'stbLoc'              =>
                                        [
                                            'lid'  => 'A=1@O=' . $iRailStation->getStandardname() . '@U=80@L=00' . $iRailStation->getHafasId() . '@B=1',
                                            'name' => '' . $iRailStation->getStandardname() . '',
                                            'type' => 'S',
                                        ],
                                    'time'                => $request->getDateTime()->format("Hms"),
                                    'getPasslist'         => false,
                                    'getTrainComposition' => false,
                                    'maxJny'              => 50
                                ]
                        ]
                ],
            'ver'       => '1.11',
            'formatted' => false,
        ];
        if ($request->getDepartureArrivalMode() == DepartureArrivalMode::MODE_ARRIVAL) {
            $postdata['svcReqL'][0]['req']['type'] = 'ARR';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata, JSON_UNESCAPED_SLASHES));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}
