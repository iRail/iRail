<?php

namespace Irail\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
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
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param Throwable                $exception
     * @return Response|JsonResponse
     *
     * @throws Throwable
     */
    public function render($request, Throwable $exception): Response|JsonResponse
    {
        if (!method_exists($exception, 'getStatusCode')) {
            return parent::render($request, $exception);
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
}
