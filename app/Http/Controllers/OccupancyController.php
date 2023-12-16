<?php

namespace Irail\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request as LumenRequest;
use Irail\Http\Requests\OccupancyReportRequest;
use Irail\Repositories\Irail\OccupancyRepository;

class OccupancyController extends BaseIrailController
{

    private OccupancyRepository $occupancyRepository;

    public function __construct(OccupancyRepository $occupancyRepository)
    {
        $this->occupancyRepository = $occupancyRepository;
    }

    public function store(OccupancyReportRequest $request)
    {
        $vehicleId = basename($request->getVehicleUri());
        $stationId = basename($request->getFromStationUri());
        return $this->outputJson($request,
            $this->occupancyRepository->recordSpitsgidsOccupancy(
                $vehicleId,
                $stationId,
                $request->getDate(),
                $request->getReportedOccupancy()
            )
        );
    }

    public function dump(LumenRequest $request)
    {
        $date = $request->has('date') ? Carbon::createFromFormat('YYYYmmdd', $request->get('date')) : Carbon::yesterday();
        return $this->outputJson($request, $this->occupancyRepository->getReports($date));
    }
}