<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

class LiveboardV1Request extends IrailHttpRequest implements LiveboardRequest, IrailV1Request
{
    use LiveboardCacheId;

    private TimeSelection $departureArrivalMode;
    private Carbon $dateTime;
    private string $stationId;

    public function __construct()
    {
        parent::__construct();
        if ($this->_request->has('id')) {
            $this->stationId = $this->parseStationId('id', $this->routeOrGet('id'));
        } else {
            $this->stationId = $this->parseStationId('station', $this->routeOrGet('station'));
        }
        $this->dateTime = $this->parseIrailV1DateTime();
        $this->departureArrivalMode = $this->parseV1DepartureArrival($this->routeOrGet('arrdep', 'departure'));
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
