<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Models\Dto\v2\LiveboardV2Converter;
use Irail\Repositories\LiveboardRepository;

class LiveboardV2Controller extends IrailController
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

    public function getLiveboardById(LiveboardRequest $request): JsonResponse
    {
        $repo = app(LiveboardRepository::class);
        $liveboardSearchResult = $repo->getLiveboard($request);
        $dto = LiveboardV2Converter::convert($request, $liveboardSearchResult);
        return $this->outputJson($request, $dto);
    }

}
