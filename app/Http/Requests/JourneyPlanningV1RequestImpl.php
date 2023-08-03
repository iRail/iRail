<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Request\InvalidRequestException;
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
        $this->destinationStationId = $this->parseStationId('to', $this->_request->get('to'));;

        try {
            $date = $this->_request->get('date') ?: date('Ymd');
            $time = $this->_request->get('time') ?: date('Hi');
            if (strlen($date) == 6) {
                $date = '20' . $date;
            }
            if (strlen($time) == 3) {
                $time = '0' . $time;
            }
            $this->dateTime = Carbon::createFromFormat('Ymd Hi', $date . ' ' . $time);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new InvalidRequestException('Invalid date/time provided');
        }

        $this->timeSelection = $this->_request->has('arrdep') ? $this->parseDepartureArrival($this->_request->get('arrdep')) : TimeSelection::DEPARTURE;
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
