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
                $this->getDateTime()->seconds(0)->getTimestamp(),
                $this->getLanguage()
            ]);
    }

    abstract function getVehicleId(): ?string;

    abstract function getDatedJourneyId(): ?string;

    abstract function getDateTime(): Carbon;

    abstract function getLanguage(): string;

}