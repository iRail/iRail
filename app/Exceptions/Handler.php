<?php

namespace Irail\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param Throwable $exception
     * @return void
     *
     * @throws Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable                $exception
     * @return Response|JsonResponse
     *
     * @throws Throwable
     */
    public function render($request, Throwable $exception): Response|JsonResponse
    {
        if (!method_exists($exception, 'getStatusCode')) {
            return response()->json(
                [
                    'code'  => $exception->getCode(),
                    'message'  => $exception->getMessage(),
                    'previous' => $exception->getPrevious(),
                    'at'    => self::getLastAppMethodCall($exception->getTrace()),
                    'stack' => array_map(fn($row) => self::formatTrace($row), $exception->getTrace())
                ],
                500,
                [
                    'Access-Control-Allow-Origin'   => '*',
                    'Access-Control-Allow-Headers'  => '*',
                    'Access-Control-Expose-Headers' => '*',
                    'Content-Type'                  => 'application/json;charset=UTF-8'
                ]);
        }

        if (str_contains($request->getUri(), '/v1/') && $request->get('format', 'xml') == 'xml') {
            Log::debug('Returning XML error');
            return response(
                "<error code=\"{$exception->getCode()}\">{$exception->getMessage()}</error>",
                $exception->getStatusCode(),
                [
                    'Access-Control-Allow-Origin'   => '*',
                    'Access-Control-Allow-Headers'  => '*',
                    'Access-Control-Expose-Headers' => '*',
                    'Content-Type'                  => 'application/xml;charset=UTF-8'
                ]
            );
        }
        return response()->json(
            [
                'code'    => $exception->getStatusCode(),
                'message' => $exception->getMessage()
            ],
            $exception->getStatusCode(),
            [
                'Access-Control-Allow-Origin'   => '*',
                'Access-Control-Allow-Headers'  => '*',
                'Access-Control-Expose-Headers' => '*',
                'Content-Type'                  => 'application/json;charset=UTF-8'
            ]);
    }

    /**
     * Get the last (most recent) entry in a stack trace originating in user-written code, skipping any vendor code.
     * @param array $trace
     * @return string
     */
    private static function getLastAppMethodCall(array $trace): string
    {
        foreach ($trace as $traceItem) {
            if (str_starts_with($traceItem['file'], '/workspace/app/')) {
                return $traceItem['file'] . ':' . $traceItem['line'];
            }
        }
        return '';
    }

    /**
     * Get the last (most recent) entry in a stack trace originating in user-written code, skipping any vendor code.
     * @param array $trace
     * @return string
     */
    private static function formatTrace(array $trace): string
    {
        return $trace['class'] . $trace['type'] . $trace['function'] . ' (' . $trace['file'] . ':' . $trace['line'] . ')';
    }
}
