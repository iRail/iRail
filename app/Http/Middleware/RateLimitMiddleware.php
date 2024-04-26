<?php

namespace Irail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Irail\Exceptions\IrailHttpException;

class RateLimitMiddleware
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
        $response = RateLimiter::attempt(
            'request_rate|' . $request->getClientIp(),
            45,
            function () use ($next, $request) {
                return $next($request);
            },
            15 // 15 seconds buckets, so 3 requests per second for each 15 seconds
        );
        if ($response === false) {
            throw new IrailHttpException(429, 'Too many requests');
        }
        return $response;
    }

}
