<?php

namespace Irail\Models;

class JourneyLeg
{
    /**
     * @var DepartureArrival[]
     */
    private array $stops;

    private JourneyMode $journeyMode;
    private ?Vehicle $vehicle;
}
