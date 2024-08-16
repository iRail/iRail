<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

trait VehicleJourneyCacheId
{
    public function getCacheId(): string
    {
        return '|VehicleJourney|' . join('|', [
                $this->getDatedJourneyId() ?: 'null',
                $this->getVehicleId() ?: 'null',
                ($this->getDateTime() ?: Carbon::now('Europe/Brussels'))->seconds(0)->getTimestamp(),
                $this->getLanguage()
            ]);
    }

    abstract public function getVehicleId(): ?string;

    abstract public function getDatedJourneyId(): ?string;

    abstract public function getDateTime(): ?Carbon;

    abstract public function getLanguage(): string;
}
