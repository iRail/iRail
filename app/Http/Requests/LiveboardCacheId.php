<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;
use DateTime;

trait LiveboardCacheId
{
    public function getCacheId(): string
    {
        return '|Liveboard|' . join('|', [
                $this->getStationId(),
                $this->getDateTime()->seconds(0)->getTimestamp(),
                $this->getDepartureArrivalMode()->name,
                $this->getLanguage()
            ]);
    }

    abstract function getStationId(): string;

    abstract function getDateTime(): Carbon;

    abstract function getDepartureArrivalMode(): TimeSelection;

    abstract function getLanguage(): string;

}