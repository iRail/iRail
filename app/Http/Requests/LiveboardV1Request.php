<?php

namespace Irail\Http\Requests;

use DateTime;

class LiveboardV1Request extends IrailHttpRequest implements LiveboardRequest, IrailV1Request
{
    use LiveboardCacheId;

    private TimeSelection $departureArrivalMode;
    private DateTime $dateTime;
    private string $stationId;

    public function __construct()
    {
        parent::__construct();
        if ($this->_request->has('id')) {
            $this->stationId = $this->parseStationId('id', $this->routeOrGet('id'));
        } else {
            $this->stationId = $this->parseStationId('station', $this->routeOrGet('station'));
        }
        $this->dateTime = $this->parseDateTime($this->get('datetime'));
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
     * @return DateTime
     */
    public function getDateTime(): DateTime
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