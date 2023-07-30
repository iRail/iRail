<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Request;
use Irail\Http\Requests\IrailHttpRequest;
use Irail\Legacy\Output\Printer;
use Laravel\Lumen\Routing\Controller as BaseController;

abstract class BaseIrailController extends BaseController
{

    protected function outputJson(Request $request, $result)
    {
        return response()->json((array) $result, 200, [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Age'     => '86400',
            'Content-Type'                 => 'application/json;charset=UTF-8'
        ]);
    }

    protected function outputV1(IrailHttpRequest $request, $result)
    {
        $printer = Printer::getPrinterInstance($request->getResponseFormat(), $result);
        try {
            return response($printer->getBody(), 200, $printer->getHeaders());
        } catch (\Exception $e) {
            return response("Failed to print response: $e", 500);
        }

    }
}
