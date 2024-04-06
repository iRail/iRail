<?php

namespace Irail\Models\Dao;

enum LogQueryType: string
{
    case LIVEBOARD = 'Liveboard';
    case JOURNEYPLANNING = 'JourneyPlanning';
    case SERVICEALERTS = 'ServiceAlerts';
    case DATEDVEHICLEJOURNEY = 'DatedVehicleJny';
    case STATIONS = 'Stations';
    case VEHICLECOMPOSITION = 'Composition';
}
