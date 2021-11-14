<?php

namespace Irail\Models;

class Occupancy
{
    // TODO: convert to enum in PHP 8.1
    private string $prognosesOccupancyUri;
    private string $prognosesOccupancyName;
    private string $reportedOccupancyUri;
    private string $reportedOccupancyName;
    private string $officialOccupancyUri;
    private string $officialOccupancyName;
}
