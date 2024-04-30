<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

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

    abstract public function getStationId(): string;

    abstract public function getDateTime(): Carbon;

    abstract public function getDepartureArrivalMode(): TimeSelection;

    abstract public function getLanguage(): string;
}
