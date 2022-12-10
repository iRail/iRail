<?php

namespace Irail\Models\Result;

use Irail\Models\Journey;
use Irail\Models\StationInfo;

class JourneyPlanningSearchResult
{

    use Cachable;

    private StationInfo $originStation;
    private StationInfo $destinationStation;
    /**
     * @var Journey[] $journeys
     */
    private array $journeys;

    /**
     * @return StationInfo
     */
    public function getOriginStation(): StationInfo
    {
        return $this->originStation;
    }

    /**
     * @return StationInfo
     */
    public function getDestinationStation(): StationInfo
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


    public function setOriginStation(?StationInfo $originStation): void
    {
        $this->originStation = $originStation;
    }

    public function setDestinationStation(?StationInfo $destinationStation): void
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
