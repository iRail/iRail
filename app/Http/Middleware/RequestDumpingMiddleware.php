<?php

namespace Irail\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Proxy\CurlProxy;

class RequestDumpingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Perform the request first
        try {
            $result = $next($request);
            if (getenv('LOG_REQUESTS') == 'ALL') {
                $this->logRequests();
            }
        } catch (InternalProcessingException|UpstreamServerException $e) {
            if (getenv('LOG_REQUESTS') == 'ALL' || getenv('LOG_REQUESTS') == 'ERROR') {
                $this->logRequests();
            }
            throw $e; // re-throw exception without changing it
        }
        return $result;
    }

    /**
     * @return void
     */
    public function logRequests(): void
    {
        $logToFile = strtolower(getenv('LOG_REQUESTS_FILE')) == 'true';
        $time = Carbon::now();
        /**
         * @var CurlProxy $curlProxy
         */
        $curlProxy = app(CurlProxy::class);
        foreach ($curlProxy->getRequests() as $index => $request) {
            Log::Warning($index . ': ' . $request->toString());
            if ($logToFile) {
                if (!file_exists(storage_path('requests/'))) {
                    mkdir(storage_path('requests/'), 0777, true);
                }
                file_put_contents(storage_path('requests/' . $time->timestamp . '_' . $index . '.txt'), $request->toFormattedString());
            }
        }
    }
}
