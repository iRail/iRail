<?php

namespace Irail\Models\Requests;

use DateTime;

class ConnectionsRequestImpl implements ConnectionsRequest
{
    use ConnectionsCacheId;

    private string $originStationId;
    private string $destinationStationId;
    private DateTime $dateTime;
    private TimeSelection $timeSelection;
    private TypeOfTransportFilter $typesOfTransport;
    private string $language;

    /**
     * @param string                $originStationId
     * @param string                $destinationStationId
     * @param DateTime              $dateTime
     * @param TimeSelection         $timeSelection
     * @param TypeOfTransportFilter $typesOfTransport
     * @param string                $language
     */
    public function __construct(string $originStationId, string $destinationStationId,
        DateTime $dateTime = new DateTime(),
        TimeSelection $timeSelection = TimeSelection::DEPARTURE,
        TypeOfTransportFilter $typesOfTransport = TypeOfTransportFilter::AUTOMATIC,
        string $language = 'en')
    {
        $this->originStationId = $originStationId;
        $this->destinationStationId = $destinationStationId;
        $this->dateTime = $dateTime;
        $this->timeSelection = $timeSelection;
        $this->typesOfTransport = $typesOfTransport;
        $this->language = $language;
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

    function getLanguage(): string
    {
        return $this->language;
    }
}
