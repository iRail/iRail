<?php

namespace Irail\Repositories\Nmbs;

use Carbon\Carbon;
use Irail\Database\OccupancyDao;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Models\Result\LiveboardSearchResult;
use Irail\Proxy\CurlProxy;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\LiveboardRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;

/**
 * This liveboard repository combines a RIV repository and an HTML liveboard repository as fallback, deciding when which source is used.
 */
class NmbsMergedLiveboardRepository implements LiveboardRepository
{
    public function getLiveboard(LiveboardRequest $request): LiveboardSearchResult
    {
        if ($this->isOutsideRivDateRange($request)) {
            // Fallback to HTML data for requests far in the past or future
            $repo = new NmbsHtmlLiveboardRepository(app(StationsRepository::class), app(CurlProxy::class),
                app(GtfsTripStartEndExtractor::class), app(OccupancyDao::class));
        } else {
            $repo = new NmbsRivLiveboardRepository(app(StationsRepository::class), app(GtfsTripStartEndExtractor::class),
                app(NmbsRivRawDataRepository::class), app(OccupancyDao::class));
        }
        return $repo->getLiveboard($request);
    }

    /**
     * @param LiveboardRequest $request
     * @return bool
     */
    public function isOutsideRivDateRange(LiveboardRequest $request): bool
    {
        return $request->getDateTime()->isBefore(Carbon::now()->subMinutes(30))
            || $request->getDateTime()->isAfter(Carbon::now()->addDays(3));
    }
}
