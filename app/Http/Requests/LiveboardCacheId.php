<?php

namespace Irail\Http\Requests;

use DateTime;

trait LiveboardCacheId
{
    public function getCacheId(): string
    {
        return '|Liveboard|' . join('|', [
                $this->getStationId(),
                $this->getDateTime()->getTimestamp(),
                $this->getDepartureArrivalMode(),
                $this->getLanguage()
            ]);
    }

    abstract function getStationId(): string;

    abstract function getDateTime(): DateTime;

    abstract function getDepartureArrivalMode(): TimeSelection;

    abstract function getLanguage(): string;

}