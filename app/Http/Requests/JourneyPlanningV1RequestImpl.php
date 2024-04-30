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
        $this->timeSelection = $this->_request->has('timeSel') ? $this->parseV1DepartureArrival($this->_request->get('timeSel')) : TimeSelection::DEPARTURE;
        if ($this->_request->has('typeOfTransport')) {
            $this->typesOfTransport = $this->parseV1TypeOfTransport();
        } else {
            $this->typesOfTransport = TypeOfTransportFilter::AUTOMATIC;
        }
    }


    public function getOriginStationId(): string
    {
        return $this->originStationId;
    }

    public function getDestinationStationId(): string
    {
        return $this->destinationStationId;
    }

    public function getDateTime(): Carbon
    {
        return $this->dateTime;
    }

    public function getTimeSelection(): TimeSelection
    {
        return $this->timeSelection;
    }

    public function getTypesOfTransport(): TypeOfTransportFilter
    {
        return $this->typesOfTransport;
    }

    /**
     * @return TypeOfTransportFilter
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function parseV1TypeOfTransport(): TypeOfTransportFilter
    {
        return match ($this->_request->get('typeOfTransport')) {
            'nointernationaltrains' => TypeOfTransportFilter::NO_INTERNATIONAL_TRAINS,
            'all', 'trains'         => TypeOfTransportFilter::TRAINS,
            default                 => TypeOfTransportFilter::AUTOMATIC,
        };
    }
}
