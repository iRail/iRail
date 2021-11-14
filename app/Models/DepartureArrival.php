<?php

namespace Irail\Models;

use DateTime;

class DepartureArrival
{
    private string $uri;

    private StationInfo $station;
    private PlatformInfo $platform;
    private Vehicle $vehicle;
    private Occupancy $occupancy;

    private string $direction;

    private ?DateTime $scheduledDepartureTime;
    private ?int $departureDelay;

    private ?DateTime $scheduledArrivalTime;
    private ?int $arrivalDelay;

    private bool $isCanceled;
    private bool $hasArrived;
    private bool $hasLeft;
    private bool $isExtratop;
}
