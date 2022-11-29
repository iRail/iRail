<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Http\Requests\LiveboardRequest;

class LiveboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getLiveboardById(LiveboardRequest $request): Response
    {

    }
}
