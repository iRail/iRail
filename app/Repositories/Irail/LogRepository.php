<?php

namespace Irail\Repositories\Irail;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Irail\Models\Dao\LogEntry;

class LogRepository
{
    public function log(string $queryType, array $query, string $userAgent, array $result = null)
    {
        DB::update('INSERT INTO RequestLog (queryType, query, userAgent, result) VALUES (?, ?, ?, ?)', [
            $queryType,
            json_encode($query, JSON_UNESCAPED_SLASHES),
            $userAgent,
            $result ? json_encode($result, JSON_UNESCAPED_SLASHES) : null
        ]);
    }

    /**
     * @param int $limit
     * @return LogEntry[]
     */
    public function readLastLogs(int $limit): array
    {
        $rows = DB::select('SELECT id, queryType, query, result, userAgent, createdAt FROM RequestLog ORDER BY createdAt DESC LIMIT ?', [$limit]);
        $entries = array_map(function ($row): LogEntry {
            return new LogEntry($row->id,
                $row->queryType,
                json_decode($row->query, associative: true),
                json_decode($row->result, associative: true),
                $row->userAgent,
                new Carbon($row->createdAt));
        }, $rows);
        return $entries;
    }
}