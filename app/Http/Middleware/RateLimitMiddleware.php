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
        $response = RateLimiter::attempt(
            'request_rate|burst|' . $request->getClientIp(),
            12,
            function () use ($request, $next) {
                return $next($request);
            },
            5 // 12 request per 5 seconds, 2 per second bursts
        );
        if ($response === false) {
            InMemoryMetrics::countRateLimitRejection();
            throw new IrailHttpException(429, 'Too many requests (short-term)');
        }
        return $response;
    }
}
