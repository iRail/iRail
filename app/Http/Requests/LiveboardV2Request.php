<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

class LiveboardV2Request extends IrailHttpRequest implements LiveboardRequest
{
    use LiveboardCacheId;

    private TimeSelection $departureArrivalMode;
    private Carbon $dateTime;
    private string $stationId;

    public function __construct()
    {
        parent::__construct();
        $this->stationId = $this->parseStationId('id', $this->routeOrGet('id'));
        $this->dateTime = $this->parseDateTime($this->_request->get('datetime'));
        $this->departureArrivalMode = $this->parseDepartureArrival($this->routeOrGet('departureArrivalMode', 'departure'));
    }

    /**
     * @return string
     */
    public function getStationId(): string
    {
        return $this->stationId;
    }

    /**
     * @return Carbon
     */
    public function getDateTime(): Carbon
    {
        return $this->dateTime;
    }

    /**
     * @return TimeSelection
     */
    public function getDepartureArrivalMode(): TimeSelection
    {
        return $this->departureArrivalMode;
    }


}