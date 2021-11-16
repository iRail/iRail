<?php

namespace Irail\Models;

class JourneyLeg
{
    /**
     * @var DepartureAndArrival[]
     */
    private array $stops;

    private JourneyMode $journeyMode;
    private ?Vehicle $vehicle;
}
