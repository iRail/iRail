<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Request\InvalidRequestException;

class JourneyPlanningV1RequestImpl extends IrailHttpRequest implements JourneyPlanningRequest, IrailV1Request
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
     * @throws InvalidRequestException
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
