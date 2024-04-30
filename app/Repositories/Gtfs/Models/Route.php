<?php

namespace Irail\Repositories\Gtfs\Models;

class Route
{
    private string $routeId;
    private string $routeShortName;

    /**
     * @param string $routeId
     * @param string $routeShortName
     */
    public function __construct(string $routeId, string $routeShortName)
    {
        $this->routeId = $routeId;
        $this->routeShortName = $routeShortName;
    }

    public function getRouteId(): string
    {
        return $this->routeId;
    }

    public function getRouteShortName(): string
    {
        return $this->routeShortName;
    }
}
