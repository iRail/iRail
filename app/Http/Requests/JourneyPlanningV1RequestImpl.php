<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class JourneyPlanningV1RequestImpl extends IrailHttpRequest implements JourneyPlanningRequest, IrailV1Request
{
    use JourneyPlanningCacheId;

    private string $originStationId;
    private string $destinationStationId;
    private Carbon $dateTime;
    private TimeSelection $timeSelection;
    private TypeOfTransportFilter $typesOfTransport;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct()
    {
        parent::__construct();
        $this->originStationId = $this->parseStationId('from', $this->_request->get('from'));
        $this->destinationStationId = $this->parseStationId('to', $this->_request->get('to'));
        $this->dateTime = $this->parseIrailV1DateTime();
        $this->timeSelection = $this->_request->has('arrdep') ? $this->parseV1DepartureArrival($this->_request->get('arrdep')) : TimeSelection::DEPARTURE;
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

    function getDateTime(): Carbon
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
