<?php

namespace Irail\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Irail\Models\Dao\LogEntry;
use Irail\Proxy\CurlHttpResponse;

class OutgoingRequestLogDao
{
    public function log(string $request_id, string $irail_request_url, int $irail_response_code, int $index, CurlHttpResponse $request): void
    {
        DB::update('INSERT INTO outgoing_request_log (irail_request_id, irail_request_url, irail_response_code, irail_request_outgoing_index, 
                                  timestamp, duration, response_code, method, url, request_body, response_body)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $request_id,
                $irail_request_url,
                $irail_response_code,
                $index,
                $request->getTimestamp()->format('Y-m-d H:i:s.v'),
                $request->getDuration(),
                $request->getResponseCode(),
                $request->getMethod(),
                $request->getUrl(),
                $request->getRequestBody(),
                $request->getResponseBody(),
            ]);
    }

    /**
     * @param int $limit
     * @return CurlHttpResponse[]
     */
    public function readLastLogs(int $limit): array
    {
        $rows = DB::select('SELECT timestamp,  method, url, request_body, response_code, duration, response_body FROM outgoing_request_log ORDER BY timestamp DESC LIMIT ?',
            [$limit]);
        return $this->transformRows($rows);
    }

    /**
     * @param array $rows
     * @return LogEntry[]
     */
    public function transformRows(array $rows): array
    {
        return array_map(function ($row): CurlHttpResponse {
            return new CurlHttpResponse(
                Carbon::parse($row->timestamp),
                $row->method,
                $row->url,
                $row->request_body,
                $row->response_code,
                $row->response_body,
                $row->duration);
        }, $rows);
    }
}