<?php

namespace Irail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $response = RateLimiter::attempt(
            'request_rate|burst|' . $clientIp,
            30,
            function () use ($request, $next) {
                return $next($request);
            },
            15 // 30 request per 15 seconds burst rate limit
        );
        if ($response === false) {
            InMemoryMetrics::countRateLimitRejection();
            throw new IrailHttpException(429, 'Too many requests (short-term)');
        }
        return $response;
    }
}
