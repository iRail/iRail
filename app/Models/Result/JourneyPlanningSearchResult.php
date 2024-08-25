<?php

namespace Irail\Models\Result;

use Irail\Models\Cachable;
use Irail\Models\Journey;
use Irail\Models\Station;

class JourneyPlanningSearchResult implements Cachable
{
    use CachableResult;

    private Station $originStation;
    private Station $destinationStation;
    /**
     * @var Journey[] $journeys
     */
    private array $journeys;

    /**
     * @return Station
     */
    public function getOriginStation(): Station
    {
        return $this->originStation;
    }

    /**
     * @return Station
     */
    public function getDestinationStation(): Station
    {
        return $this->destinationStation;
    }

    /**
     * @return Journey[]
     */
    public function getJourneys(): array
    {
        return $this->journeys;
    }


    public function setOriginStation(?Station $originStation): void
    {
        $this->originStation = $originStation;
    }

    public function setDestinationStation(?Station $destinationStation): void
    {
        $this->destinationStation = $destinationStation;
    }

    /**
     * @param Journey[] $journeys
     * @return void
     */
    public function setJourneys(array $journeys): void
    {
        $this->journeys = $journeys;
    }
}
