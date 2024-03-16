<?php

namespace Irail\Repositories\Gtfs\Models;

enum PickupDropoffType: int
{
    /**
     * Regularly scheduled pickup/dropoff.
     */
    case    ALWAYS = 0;

    /**
     * No pickup/dropoff available.
     */
    case    NEVER = 1;

    /**
     * Must phone agency to arrange pickup/dropoff.
     */
    case    BOOKING_REQUIRED = 2;

    /**
     * Must coordinate with driver to arrange pickup/dropoff.
     */
    case    REQUEST_STOP = 3;

}