<?php

namespace Irail\Models\Requests;

use DateTime;

trait VehicleJourneyCacheId
{
    public function getCacheId(): string
    {
        return '|VehicleJourney|' . join('|', [
                $this->getDatedJourneyId() ?: 'null',
                $this->getVehicleId() ?: 'null',
                $this->getDateTime()->getTimestamp(),
                $this->getLanguage()
            ]);
    }

    abstract function getVehicleId(): ?string;

    abstract function getDatedJourneyId(): ?string;

    abstract function getDateTime(): DateTime;

    abstract function getLanguage(): string;

}