<?php

namespace Irail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Irail\Database\OutgoingRequestLogDao;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Http\Requests\RequestUuidHelper;
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
        $logConfig = getenv('LOG_REQUESTS');
        /** @var Response $result */
        $result = $next($request);
        if ($logConfig == 'ALL' || ($logConfig == 'ERROR' && $result->getStatusCode() == 500)) {
            $this->logOutgoingRequests($request, $result);
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
        $request_id = RequestUuidHelper::getRequestId($request);
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
