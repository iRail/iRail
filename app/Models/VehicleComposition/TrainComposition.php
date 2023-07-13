<?php

namespace Irail\Models\VehicleComposition;

class TrainComposition
{
    /**
     * @var String internal source of this data, for example "Atlas".
     */
    private string $compositionSource;

    /**
     * @var TrainCompositionUnit[] the units in this composition.
     */
    private array $units;

    /**
     * @param string                 $compositionSource
     * @param TrainCompositionUnit[] $units
     */
    public function __construct(string $compositionSource, array $units)
    {
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

    public function getUnit(int $i)
    {
        return $this->units[$i];
    }

    public function getLength(): int
    {
        return count($this->units);
    }


}
