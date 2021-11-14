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
}
