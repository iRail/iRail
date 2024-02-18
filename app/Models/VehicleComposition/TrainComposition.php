<?php

namespace Irail\Models\VehicleComposition;

use Irail\Models\Station;
use Irail\Models\Vehicle;

class TrainComposition
{
    private Vehicle $vehicle;

    private Station $origin;

    private Station $destination;

    /**
     * @var String internal source of this data, for example "Atlas".
     */
    private string $compositionSource;

    /**
     * @var TrainCompositionUnit[] the units in this composition.
     */
    private array $units;

    /**
     * @param Vehicle                $vehicle
     * @param Station $origin
     * @param Station $destination
     * @param string                 $compositionSource
     * @param TrainCompositionUnit[] $units
     */
    public function __construct(Vehicle $vehicle, Station $origin, Station $destination, string $compositionSource, array $units)
    {
        $this->vehicle = $vehicle;
        $this->origin = $origin;
        $this->destination = $destination;
        $this->compositionSource = $compositionSource;
        $this->units = $units;
    }

    /**
     * @return string
     */
    public function getCompositionSource(): string
    {
        return $this->compositionSource;
    }

    /**
     * @return array
     */
    public function getUnits(): array
    {
        return $this->units;
    }

    public function getUnit(int $i): TrainCompositionUnit
    {
        return $this->units[$i];
    }

    public function getLength(): int
    {
        return count($this->units);
    }

    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function getOrigin(): Station
    {
        return $this->origin;
    }

    public function getDestination(): Station
    {
        return $this->destination;
    }

}
