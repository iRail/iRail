<?php

namespace Irail\Database;

/**
 * An enum indicating how the OccupancyDAO should optimise database queries
 */
enum OccupancyDaoPerformanceMode
{
    case VEHICLE;
    case STATION;
}
