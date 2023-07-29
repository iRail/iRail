<?php

namespace Irail\Repositories\Irail;

use Illuminate\Support\Facades\DB;

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
}