<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Http\Requests\LiveboardRequestImpl;
use Irail\Models\Dto\v1\LiveboardV1Converter;
use Irail\Repositories\LiveboardRepository;

class LiveboardV1Controller extends BaseIrailController
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

    public function getLiveboardById(LiveboardRequestImpl $request): Response
    {
        $repo = app(LiveboardRepository::class);
        $liveboardSearchResult = $repo->getLiveboard($request);
        $dataRoot = LiveboardV1Converter::convert($request, $liveboardSearchResult);
        return $this->outputV1($request, $dataRoot);
    }

}
