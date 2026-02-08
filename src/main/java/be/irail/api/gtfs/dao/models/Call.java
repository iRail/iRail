package be.irail.api.gtfs.dao.models;

import be.irail.api.gtfs.reader.models.Stop;
import be.irail.api.gtfs.reader.models.StopTime;

/**
 * Represents a call (stop) on a journey.
 */
public record Call(StopTime stopTime, Stop platform, Stop station) {
}
