<?php

namespace Irail\Http\Requests;

use DateTime;
use Irail\Exceptions\Request\InvalidRequestException;

class LiveboardRequestImpl extends IrailHttpRequest implements LiveboardRequest
{
    use LiveboardCacheId;

    private TimeSelection $departureArrivalMode;
    private DateTime $dateTime;
    private string $stationId;

    public function __construct()
    {
        parent::__construct();
        $this->stationId = $this->parseStationId('id', $this->routeOrGet('id'));
        $this->dateTime = $this->parseDateTime($this->get('datetime'));
        $this->departureArrivalMode = $this->parseDepartureArrival($this->routeOrGet('arrdep'));
    }

    /**
     * @return string
     * @throws InvalidRequestException
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