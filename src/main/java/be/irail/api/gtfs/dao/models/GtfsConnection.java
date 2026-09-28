package be.irail.api.gtfs.dao.models;

import be.irail.api.gtfs.reader.models.Route;
import be.irail.api.gtfs.reader.models.Stop;
import be.irail.api.gtfs.reader.models.StopTime;
import be.irail.api.gtfs.reader.models.Trip;

import java.time.LocalDate;

/**
 * One directed, stop-to-stop connection from an active GTFS trip.
 */
public record GtfsConnection(
        LocalDate tripStartDate,
        Trip trip,
        Route route,
        StopTime departureCall,
        StopTime arrivalCall,
        Stop departureStop,
        Stop arrivalStop
) {
}