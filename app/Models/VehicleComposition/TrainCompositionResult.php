<?php

namespace Irail\Models\VehicleComposition;

use Irail\Models\Result\Cachable;

class TrainCompositionResult
{
    use Cachable;

    /**
     * @var $segment TrainCompositionOnSegment[] A list of all segments with their own composition for this train ride.
     */
    private array $segments;

    /**
     * @param TrainCompositionOnSegment[] $segments
     */
    public function __construct(array $segments)
    {
        $this->segments = $segments;
    }

    /**
     * @return array
     */
    public function getSegments(): array
    {
        return $this->segments;
    }
}
