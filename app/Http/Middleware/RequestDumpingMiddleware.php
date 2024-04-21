<?php

namespace Irail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Irail\Database\OutgoingRequestLogDao;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Proxy\CurlProxy;
use Symfony\Component\HttpFoundation\Response;

class RequestDumpingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Perform the request first
        try {
            /** @var Response $result */
            $result = $next($request);
            if (getenv('LOG_REQUESTS') == 'ALL') {
                $this->logOutgoingRequests($request, $result);
            }
        } catch (InternalProcessingException|UpstreamServerException $e) {
            if (getenv('LOG_REQUESTS') == 'ALL' || getenv('LOG_REQUESTS') == 'ERROR') {
                $this->logOutgoingRequests($request, $e);
            }
            throw $e; // re-throw exception without changing it
        }
        return $result;
    }

    /**
     * Log outgoing requests (to GTFS files, to the NMBS api, etc...) to the database for debugging.
     * @param Request                                                      $request The iRail request which caused the outgoing requests
     * @param Response|InternalProcessingException|UpstreamServerException $result The iRail response or exception based on the fetched data
     * @return void
     */
    public function logOutgoingRequests(Request $request, Response|InternalProcessingException|UpstreamServerException $result): void
    {
        $request_id = (string)Str::orderedUuid();
        $irail_request_url = $request->getUri();
        $irail_response_code = $result->getStatusCode();
        /**
         * @var CurlProxy $curlProxy
         */
        $curlProxy = app(CurlProxy::class);

        /** @var OutgoingRequestLogDao $requestLogDao */
        $requestLogDao = app(OutgoingRequestLogDao::class);
        foreach ($curlProxy->getRequests() as $index => $request) {
            $requestLogDao->log($request_id, $irail_request_url, $irail_response_code, $index + 1, $request);
        }
    }
}
