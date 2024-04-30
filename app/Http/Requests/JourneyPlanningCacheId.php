<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

trait JourneyPlanningCacheId
{
    public function getCacheId(): string
    {
        return '|Connections|' . join('|', [
                $this->getOriginStationId(),
                $this->getDestinationStationId(),
                $this->getDateTime()->seconds(0)->getTimestamp(),
                $this->getTimeSelection()->name,
                $this->getTypesOfTransport()->name,
                $this->getLanguage()
            ]);
    }

    abstract public function getOriginStationId(): string;

    abstract public function getDestinationStationId(): string;

    abstract public function getDateTime(): Carbon;

    abstract public function getTimeSelection(): TimeSelection;

    abstract public function getTypesOfTransport(): TypeOfTransportFilter;

    /**
     * Get the requested response language, as an ISO2 code.
     * @return string
     */
    abstract public function getLanguage(): string;
}
