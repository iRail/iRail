<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Irail\Database\LogDao;
use Irail\Models\Dao\LogQueryType;

class LogController extends BaseIrailController
{
    private LogDao $logRepository;
    private int $cacheDuration;

    public function __construct(LogDao $logRepository)
    {
        $this->logRepository = $logRepository;
        $this->cacheDuration = env('CACHE_DURATION_LOGS_ENDPOINT', 10);
    }

    public function getLogs(Request $request)
    {
        // Prevent high database load by caching this for at least a couple seconds, effectively rate-limiting everyone to 60/cacheDuration requests per second.
        $data = Cache::remember('logs', $this->cacheDuration, function () use ($request) {
            $logs = $this->logRepository->readLastLogs(1000);
            $data = array_map(fn($logEntry) => [
                'querytype'  => $this->getName($logEntry->getQueryType()),
                'querytime'  => $logEntry->getCreatedAt(),
                'query'      => $logEntry->getQuery() + ($logEntry->getResult() ?: []),
                'user_agent' => $logEntry->getUserAgent()
            ], $logs);
            return $data;
        });

        return $this->outputJson($request, $data);
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
