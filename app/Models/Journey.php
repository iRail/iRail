<?php

namespace Irail\Models;

class Journey
{
    /**
     * @var JourneyLeg[]
     */
    private array $legs;

    /**
     * @var Note[]
     */
    private array $notes;

    /**
     * @var array ServiceAlertNote[]
     */
    private array $serviceAlerts;

    public function setDurationSeconds(float|int $transformIso8601Duration)
    {

    }
}
