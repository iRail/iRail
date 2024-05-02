<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Request;
use Irail\Database\LogDao;
use Irail\Models\Dao\LogQueryType;

class LogController extends BaseIrailController
{
    private LogDao $logRepository;

    public function __construct(LogDao $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    public function getLogs(Request $request)
    {
        $logs = $this->logRepository->readLastLogs(1000);
        $json = array_map(function ($logEntry) {
            $result = [
                'querytype'  => $this->getName($logEntry->getQueryType()),
                'querytime'  => $logEntry->getCreatedAt(),
                'query'      => $logEntry->getQuery(),
                'user_agent' => $logEntry->getUserAgent()
            ];
            if ($logEntry->getResult()) {
                $result['journeyoptions'] = $logEntry->getResult();
            }
            return $result;
        }, $logs);
        return $this->outputJson($request, $json);
    }

    private function getName(LogQueryType $getQueryType)
    {
        switch ($getQueryType) {
            case LogQueryType::LIVEBOARD:
                return 'Liveboard';
            case LogQueryType::JOURNEYPLANNING:
                return 'Connections';
            case LogQueryType::DATEDVEHICLEJOURNEY:
                return 'VehicleInformation';
            case LogQueryType::VEHICLECOMPOSITION:
                return 'Composition';
            case LogQueryType::STATIONS:
                return 'Stations';
            case LogQueryType::SERVICEALERTS:
                return 'Disturbances';
        }
        return '';
    }
}
