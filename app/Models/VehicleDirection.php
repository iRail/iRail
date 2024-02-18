<?php

namespace Irail\Models;

class VehicleDirection
{
    private ?Station $station;
    private string $name;

    public function __construct(string $name, ?Station $station)
    {
        $this->name = $name;
        $this->station = $station;
    }

    /**
     * @return Station|null
     */
    public function getStation(): ?Station
    {
        return $this->station;
    }

    /**
     * @param Station|null $station
     */
    public function setStation(?Station $station): void
    {
        $this->station = $station;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }


}