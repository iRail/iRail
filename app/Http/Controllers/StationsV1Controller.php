<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Database\LogDao;
use Irail\Http\Requests\StationsV1Request;
use Irail\Http\Responses\v1\StationsV1Converter;
use Irail\Models\Dao\LogQueryType;
use Irail\Repositories\Irail\StationsRepository;

class StationsV1Controller extends BaseIrailController
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

    public function list(StationsV1Request $request): Response
    {
        $repo = app(StationsRepository::class);
        $stations = $repo->findAllStations();
        $dataRoot = StationsV1Converter::convert($request, $stations);
        $this->logRequest($request);
        return $this->outputV1($request, $dataRoot);
    }

    /**
     * @param StationsV1Request $request
     * @return void
     */
    public function logRequest(StationsV1Request $request): void
    {
        $query = [
            'language'      => $request->getLanguage(),
            'serialization' => $request->getResponseFormat(),
            'version'       => 1
        ];
        app(LogDao::class)->log(LogQueryType::STATIONS, $query, $request->getUserAgent());
    }
}
