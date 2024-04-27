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
        return $this->rateLimitedLongTerm($request, $next);
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function rateLimitedLongTerm(Request $request, Closure $next)
    {
        $response = RateLimiter::attempt(
            'request_rate|long-term|' . $request->getClientIp(),
            1800,
            function () use ($request, $next) {
                return $this->rateLimitedShortTerm($request, $next);
            },
            3600 // 1800 requests per hour, one per 2 seconds
        );
        if ($response === false) {
            InMemoryMetrics::countRateLimitRejection();
            throw new IrailHttpException(429, 'Too many requests (long-term)');
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function rateLimitedShortTerm(Request $request, Closure $next)
    {
        $response = RateLimiter::attempt(
            'request_rate|burst|' . $request->getClientIp(),
            30,
            function () use ($request, $next) {
                return $next($request);
            },
            10 // 30 request per 10 seconds, 3 per second bursts
        );
        if ($response === false) {
            throw new IrailHttpException(429, 'Too many requests (long-term)');
        }
        return $response;
    }

}
