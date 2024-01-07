<?php

namespace Irail\Models;

class VehicleDirection
{
    private ?StationInfo $station;
    private string $name;

    public function __construct(string $name, ?StationInfo $station)
    {
        $this->name = $name;
        $this->station = $station;
    }

    /**
     * @return StationInfo|null
     */
    public function getStation(): ?StationInfo
    {
        return $this->station;
    }

    /**
     * @param StationInfo|null $station
     */
    public function setStation(?StationInfo $station): void
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