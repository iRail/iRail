<?php

namespace Irail\Models;

class DepartureAndArrival
{
    private ?StationBoardEntry $arrival = null;
    private ?StationBoardEntry $departure = null;

    /**
     * @return string
     */
    public function getUri(): ?string
    {
        return $this->getDeparture()?->getUri();
    }

    /**
     * @return StationBoardEntry|null
     */
    public function getArrival(): ?StationBoardEntry
    {
        return $this->arrival;
    }

    /**
     * @param StationBoardEntry|null $arrival
     * @return DepartureAndArrival
     */
    public function setArrival(?StationBoardEntry $arrival): DepartureAndArrival
    {
        $this->arrival = $arrival;
        return $this;
    }

    /**
     * @return StationBoardEntry|null
     */
    public function getDeparture(): ?StationBoardEntry
    {
        return $this->departure;
    }

    /**
     * @param StationBoardEntry|null $departure
     * @return DepartureAndArrival
     */
    public function setDeparture(?StationBoardEntry $departure): DepartureAndArrival
    {
        $this->departure = $departure;
        return $this;
    }

}
