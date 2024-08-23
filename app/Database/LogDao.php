<?php

namespace Irail\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Irail\Models\Dao\LogEntry;
use Irail\Models\Dao\LogQueryType;

class LogDao
{
    /**
     * How often request logs should be flushed to the database. E.g. every 10th or 100th request.
     */
    public static function getFlushInterval(): int
    {
        return env('REQUEST_LOG_FLUSH_BUFFER', 100);
    }

    /**
     * Log data to the request log table. This method will keep data in memory until there are enough rows to write to the disk.
     * Some request may be lost if the server is restarted inbetween flushes, but this is acceptable since this data isn't critical, but performance is.
     *
     * @param LogQueryType $queryType
     * @param array        $query
     * @param string       $userAgent
     * @param array|null   $result
     * @return void
     */
    public function log(LogQueryType $queryType, array $query, string $userAgent, array $result = null)
    {
        $id = apcu_inc('Irail|LogDao|insertId'); // will initialize at 1
        $data = [
            'query_type' => $queryType->value,
            'query'      => json_encode($query, JSON_UNESCAPED_SLASHES),
            'user_agent' => $this->maskEmailAddress($userAgent),
            'result'     => $result ? json_encode($result, JSON_UNESCAPED_SLASHES) : null,
            'created_at' => Carbon::now()->utc()->format('Y-m-d H:i:s')
        ];
        // Store in memory so we don't need to write to the database on every request
        apcu_store('Irail|LogDao|log|' . $id, $data, 1800); // Store until flushed, but never more than 30 minutes
        $flushInterval = $this->getFlushInterval();
        if ($id % $flushInterval == 0) {
            Log::info('Flushing request log data');

            $lastFlushId = apcu_fetch('Irail|LogDao|lastFlushId');
            if (!$lastFlushId) {
                $lastFlushId = $id - $flushInterval;
            }

            $start = time();

            try {
                // Flush to database
                $sqlData = [];
                $keysToClear = [];

                for ($i = $lastFlushId + 1; $i <= $id; $i++) {
                    $sqlData[] = apcu_fetch('Irail|LogDao|log|' . $i);
                    $keysToClear[] = 'Irail|LogDao|log|' . $i;
                }
                DB::table('request_log')->insert($sqlData);
                $duration = time() - $start;
                apcu_delete($keysToClear); // Remove from memory cache
                apcu_store('Irail|LogDao|lastFlushId', $id);
                Log::info("Flushed request log data in $duration seconds");
            } catch (\Exception $exception) {
                // Logging to database should never negatively affect requests
                Log::error("Failed to store logs in database: {$exception->getMessage()}");
            }
        }
    }

    /**
     * @param int $limit
     * @return LogEntry[]
     */
    public function readLastLogs(int $limit): array
    {
        // order by id, which is incremental. Id is already sorted in the database, while created_at will force db to sort the entire table
        $rows = DB::select('SELECT id, query_type, query, result, user_agent, created_at FROM request_log ORDER BY id DESC LIMIT ?', [$limit]);
        return $this->transformRows($rows);
    }

    /**
     * @param int $minutes
     * @return LogEntry[]
     */
    public function readLogsPastMinutes(int $minutes): array
    {
        $startTime = Carbon::now()->utc()->subMinutes($minutes)->format('Y-m-d H:i:s');
        // Database timestamps are UTC
        $rows = DB::select(
            'SELECT id, query_type, query, result, user_agent, created_at FROM request_log 
                                                             WHERE created_at >= ? ORDER BY created_at',
            ["$startTime"]
        );
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
            return new LogEntry(
                $row->id,
                LogQueryType::tryFrom($row->query_type),
                json_decode($row->query, associative: true),
                json_decode($row->result, associative: true),
                $row->user_agent,
                new Carbon($row->created_at)
            );
        }, $rows);
    }
}
