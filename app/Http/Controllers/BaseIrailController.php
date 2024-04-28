<?php

namespace Irail\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Irail\Http\Requests\IrailHttpRequest;
use Irail\Legacy\Output\Printer;
use Laravel\Lumen\Routing\Controller as BaseController;

abstract class BaseIrailController extends BaseController
{

    protected function outputJson(Request $request, $result, $cacheTtlSeconds = 15)
    {
        return response()->json((array)$result, 200, [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Age'     => '86400',
            'Content-Type'  => 'application/json;charset=UTF-8',
            'Cache-Control' => "public, max-age=$cacheTtlSeconds",
        ]);
    }

    protected function outputV1(IrailHttpRequest $request, $result, $cacheTtlSeconds = 15)
    {
        $printer = Printer::getPrinterInstance($request->getResponseFormat(), $result);
        try {
            $headers = $printer->getHeaders();
            $headers['Cache-Control'] = "public, max-age=$cacheTtlSeconds";
            return response($printer->getBody(), 200, $headers);
        } catch (Exception $e) {
            return response("Failed to print response: $e", 500);
        }

    }
}
