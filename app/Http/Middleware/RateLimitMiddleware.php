<?php

namespace Irail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Irail\Exceptions\IrailHttpException;
use Irail\Util\InMemoryMetrics;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return $this->rateLimitedShortTerm($request, $next);
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function rateLimitedShortTerm(Request $request, Closure $next)
    {
        $clientIp = $request->getClientIp();

        if ($request->hasHeader('do-connecting-ip')) {
            // Read from the right HTTP header when deployed on DigitalOcean App Platform
            $clientIp = $request->header('do-connecting-ip');
        }

        $blockedIps = getenv('IP_BLOCKLIST');
        if (!empty($blockedIps) && in_array($clientIp, explode(",",$blockedIps))) {
            Log::info("Blocked client ". $clientIp . ", present on blocklist");
            throw new IrailHttpException(429, 'Too many requests (permanent blocklist)');
        }

        $response = RateLimiter::attempt(
            'request_rate|burst|' . $clientIp,
            env('CLIENT_RATE_LIMIT', 30),
            function () use ($request, $next) {
                return $next($request);
            },
            env('CLIENT_RATE_LIMIT_INTERVAL_SECONDS', 15)
        );
        if ($response === false) {
            InMemoryMetrics::countRateLimitRejection();
            throw new IrailHttpException(429, 'Too many requests (short-term)');
        }
        return $response;
    }
}
