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
        $dateTime = $this->_request->get('date', Carbon::now()->format('dmy')) . ' ' . $this->_request->get('time', Carbon::now()->format('Hi'));
        $this->dateTime = $this->parseDateTime($dateTime, 'dmy Hi');
        $this->departureArrivalMode = $this->parseDepartureArrival($this->routeOrGet('arrdep', 'departure'));
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