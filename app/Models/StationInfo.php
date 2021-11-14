<?php

namespace Irail\Models;

class StationInfo
{
    private string $id;
    private string $uri;

    private string $stationName;
    private string $localizedStationName;

    private ?float $latitude;
    private ?float $longitude;
}
