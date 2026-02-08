package be.irail.api.gtfs.dao.models;

import be.irail.api.gtfs.reader.models.Route;
import be.irail.api.gtfs.reader.models.Trip;

import java.util.List;

/**
 * Represents a journey (trip) with its route and all its calls.
 */
public record Journey(Route route, Trip trip, List<Call> calls) {
}
