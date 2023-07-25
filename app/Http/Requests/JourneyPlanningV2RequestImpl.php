<?php

namespace Irail\Http\Requests;

use DateTime;

class JourneyPlanningV2RequestImpl extends IrailHttpRequest implements JourneyPlanningRequest
{
    use ConnectionsCacheId;

    private string $originStationId;
    private string $destinationStationId;
    private DateTime $dateTime;
    private TimeSelection $timeSelection;
    private TypeOfTransportFilter $typesOfTransport;

    /**
     * @param string                $originStationId
     * @param string                $destinationStationId
     * @param DateTime              $dateTime
     * @param TimeSelection         $timeSelection
     * @param TypeOfTransportFilter $typesOfTransport
     * @param string                $language
     */
    public function __construct()
    {
        parent::__construct();
        $this->originStationId = $this->parseStationId('from', $this->routeOrGet('from'));
        $this->destinationStationId = $this->parseStationId('to', $this->routeOrGet('to'));;
        $this->dateTime = $this->parseDateTime($this->_request->get('datetime'));
        $this->timeSelection = $this->routeOrGet('arrdep') ? $this->parseDepartureArrival($this->routeOrGet('arrdep')) : TimeSelection::DEPARTURE;
        $this->typesOfTransport = TypeOfTransportFilter::AUTOMATIC; // $this->routeOrGet('typeOfTransport'); // TODO: implement
    }


    function getOriginStationId(): string
    {
        return $this->originStationId;
    }

    function getDestinationStationId(): string
    {
        return $this->destinationStationId;
    }

    function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    function getTimeSelection(): TimeSelection
    {
        return $this->timeSelection;
    }

    function getTypesOfTransport(): TypeOfTransportFilter
    {
        return $this->typesOfTransport;
    }
}
