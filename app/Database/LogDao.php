<?php

namespace Irail\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Irail\Models\Dao\LogEntry;
use Irail\Models\Dao\LogQueryType;

class LogDao
{
    public function log(LogQueryType $queryType, array $query, string $userAgent, array $result = null)
    {
        DB::update('INSERT INTO request_log (query_type, query, user_agent, result) VALUES (?, ?, ?, ?)', [
            $queryType->value,
            json_encode($query, JSON_UNESCAPED_SLASHES),
            $this->maskEmailAddress($userAgent),
            $result ? json_encode($result, JSON_UNESCAPED_SLASHES) : null
        ]);
    }

    /**
     * @param int $limit
     * @return LogEntry[]
     */
    public function readLastLogs(int $limit): array
    {
        $rows = DB::select('SELECT id, query_type, query, result, user_agent, created_at FROM request_log ORDER BY created_at DESC LIMIT ?', [$limit]);
        return $this->transformRows($rows);
    }

    /**
     * @param int $minutes
     * @return LogEntry[]
     */
    public function readLogsPastMinutes(int $minutes): array
    {
        $startTime = Carbon::now()->subMinutes($minutes)->format('Y-m-d H:i:s');
        var_dump($startTime);
        $rows = DB::select(
            'SELECT id, query_type, query, result, user_agent, created_at FROM request_log 
                                                             WHERE created_at >= ? ORDER BY created_at',
            ["$startTime"]
        );
        return $this->transformRows($rows);
    }

    /**
     * @param Carbon $date
     * @return LogEntry[]
     */
    public function readLogsForDate(Carbon $date): array
    {
        $rows = DB::select('SELECT id, query_type, query, result, user_agent, created_at FROM request_log WHERE DATE(created_at) = ? ORDER BY created_at',
            [$date->format('Y-m-d')]);
        return $this->transformRows($rows);
    }

    /**
     * Obfuscate an email address in a user agent. abcd@defg.be becomes a***@d***.be.
     *
     * @param $userAgent
     * @return string
     */
    private function maskEmailAddress($userAgent): string
    {
        // Extract information
        $hasMatch = preg_match('/([^() @]+)@([^() @]+)\.(\w{2,})/', $userAgent, $matches);
        if (!$hasMatch) {
            // No mail address in this user agent.
            return $userAgent;
        }
        $mailReceiver = substr($matches[1], 0, 1) . str_repeat('*', strlen($matches[1]) - 1);
        $mailDomain = substr($matches[2], 0, 1) . str_repeat('*', strlen($matches[2]) - 1);

        $obfuscatedAddress = $mailReceiver . '@' . $mailDomain . '.' . $matches[3];

        return preg_replace('/([^() ]+)@([^() ]+)\.(\w{2,})/', $obfuscatedAddress, $userAgent);
    }

    /**
     * @param array $rows
     * @return LogEntry[]
     */
    public function transformRows(array $rows): array
    {
        return array_map(function ($row): LogEntry {
            return new LogEntry($row->id,
                LogQueryType::tryFrom($row->query_type),
                json_decode($row->query, associative: true),
                json_decode($row->result, associative: true),
                $row->user_agent,
                new Carbon($row->created_at));
        }, $rows);
    }
}