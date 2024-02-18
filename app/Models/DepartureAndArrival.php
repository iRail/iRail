<?php

namespace Irail\Models;

use Irail\Exceptions\Internal\InternalProcessingException;

class DepartureAndArrival
{
    private ?DepartureOrArrival $arrival = null;
    private ?DepartureOrArrival $departure = null;

    /**
     * @return string
     */
    public function getUri(): ?string
    {
        return $this->getDeparture()?->getDepartureUri();
    }

    /**
     * @return DepartureOrArrival|null
     */
    public function getArrival(): ?DepartureOrArrival
    {
        return $this->arrival;
    }

    /**
     * @param DepartureOrArrival|null $arrival
     * @return DepartureAndArrival
     */
    public function setArrival(?DepartureOrArrival $arrival): DepartureAndArrival
    {
        $this->arrival = $arrival;
        return $this;
    }

    /**
     * @return DepartureOrArrival|null
     */
    public function getDeparture(): ?DepartureOrArrival
    {
        return $this->departure;
    }

    /**
     * @param DepartureOrArrival|null $departure
     * @return DepartureAndArrival
     */
    public function setDeparture(?DepartureOrArrival $departure): DepartureAndArrival
    {
        $this->departure = $departure;
        return $this;
    }

    /**
     * @return Station
     * @throws InternalProcessingException
     */
    public function getStation(): Station
    {
        if ($this->departure) {
            return $this->departure->getStation();
        }
        if ($this->arrival) {
            return $this->arrival->getStation();
        }
        throw new InternalProcessingException('Trying to read the station from a DepartureAndArrival which neither has a departure nor arrival');
    }

}
