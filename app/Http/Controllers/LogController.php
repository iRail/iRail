<?php

namespace Irail\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Irail\Database\LogDao;

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
        $json = array_map(fn($logEntry) => [
            'querytype'  => $logEntry->getQueryType(),
            'querytime'  => $logEntry->getCreatedAt(),
            'query'      => $logEntry->getQuery() + ($logEntry->getResult() ?: []),
            'user_agent' => $logEntry->getUserAgent()
        ], $logs);
        return $this->outputJson($request, $json);
    }

    public function getLogsForDate(Request $request)
    {
        $date = Carbon::createFromFormat('Ymd', $request->route('date'));
        $logs = $this->logRepository->readLogsForDate($date);
        $json = array_map(fn($logEntry) => [
            'querytype'  => $logEntry->getQueryType(),
            'querytime'  => $logEntry->getCreatedAt(),
            'query'      => $logEntry->getQuery() + ($logEntry->getResult() ?: []),
            'user_agent' => $logEntry->getUserAgent()
        ], $logs);
        return $this->outputJson($request, $json);
    }
}