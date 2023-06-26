<?php

namespace Irail\Http\Requests;

use DateTime;

trait ConnectionsCacheId
{
    public function getCacheId(): string
    {
        return '|Connections|' . join('|', [
                $this->getOriginStationId(),
                $this->getDestinationStationId(),
                $this->getDateTime()->getTimestamp(),
                $this->getTimeSelection()->name,
                $this->getTypesOfTransport()->name,
                $this->getLanguage()
            ]);
    }

    abstract function getOriginStationId(): string;

    abstract function getDestinationStationId(): string;

    abstract function getDateTime(): DateTime;

    abstract function getTimeSelection(): TimeSelection;

    abstract function getTypesOfTransport(): TypeOfTransportFilter;

    /**
     * Get the requested response language, as an ISO2 code.
     * @return string
     */
    abstract function getLanguage(): string;
}