package be.irail.api.dto;

import java.time.LocalDateTime;
import java.time.OffsetDateTime;
import java.time.format.DateTimeFormatter;

/**
 * Represents a vehicle stop, which can be either a departure from or an arrival at a station.
 * Contains scheduling, platform, delay, and occupancy information for the stop.
 */
public class DepartureOrArrival {
    private Vehicle vehicle;
    private StationDto station;
    private PlatformInfo platform;
    private LocalDateTime scheduledDateTime;
    private int delay = 0;
    private boolean isCancelled = false;
    private boolean isReported = false;
    private boolean isExtra = false;
    private DepartureArrivalState status;
    private OccupancyInfo occupancy;

    /**
     * Gets the station where this stop occurs.
     * @return the station
     */
    public StationDto getStation() {
        return this.station;
    }

    /**
     * Sets the station where this stop occurs.
     * @param station the station to set
     * @return this instance for chaining
     */
    public DepartureOrArrival setStation(StationDto station) {
        this.station = station;
        return this;
    }

    /**
     * Gets the platform information for this stop.
     * @return the platform info
     */
    public PlatformInfo getPlatform() {
        return this.platform;
    }

    /**
     * Checks if platform information is available for this stop.
     * @return true if platform info is available
     */
    public boolean isPlatformInfoAvailable() {
        return this.platform != null;
    }

    /**
     * Sets the platform information for this stop.
     * @param platform the platform info to set
     * @return this instance for chaining
     */
    public DepartureOrArrival setPlatform(PlatformInfo platform) {
        this.platform = platform;
        return this;
    }

    /**
     * Gets the scheduled date and time for this stop.
     * @return the scheduled date time
     */
    public LocalDateTime getScheduledDateTime() {
        return this.scheduledDateTime;
    }

    /**
     * Gets the real-time date and time for this stop, including any delay.
     * @return the real-time date time, or null if scheduled time is not set
     */
    public LocalDateTime getRealtimeDateTime() {
        if (this.scheduledDateTime == null) return null;
        return this.scheduledDateTime.plusSeconds(this.delay);
    }

    /**
     * Sets the scheduled date and time for this stop.
     * @param scheduledDateTime the scheduled date time to set
     * @return this instance for chaining
     */
    public DepartureOrArrival setScheduledDateTime(LocalDateTime scheduledDateTime) {
        this.scheduledDateTime = scheduledDateTime;
        return this;
    }

    /**
     * Gets the delay for this stop in seconds.
     * @return the delay in seconds
     */
    public int getDelay() {
        return this.delay;
    }

    /**
     * Sets the delay for this stop in seconds.
     * @param delay the delay in seconds
     * @return this instance for chaining
     */
    public DepartureOrArrival setDelay(int delay) {
        this.delay = delay;
        return this;
    }

    /**
     * Checks if this stop is cancelled.
     * @return true if cancelled
     */
    public boolean isCancelled() {
        return this.isCancelled;
    }

    /**
     * Sets whether this stop is cancelled.
     * @param isCancelled true if cancelled
     * @return this instance for chaining
     */
    public DepartureOrArrival setIsCancelled(boolean isCancelled) {
        this.isCancelled = isCancelled;
        return this;
    }

    /**
     * Checks if this stop has been reported as "passed" in real-time.
     * @return true if reported
     */
    public boolean isReported() {
        return this.isReported;
    }

    /**
     * Sets whether this stop has been reported as "passed" in real-time.
     * @param isReported true if reported
     * @return this instance for chaining
     */
    public DepartureOrArrival setIsReported(boolean isReported) {
        this.isReported = isReported;
        return this;
    }

    /**
     * Checks if this is an extra (unscheduled) stop.
     * @return true if it is an extra stop
     */
    public boolean isExtra() {
        return this.isExtra;
    }

    /**
     * Sets whether this is an extra (unscheduled) stop.
     * @param isExtra true if it is an extra stop
     * @return this instance for chaining
     */
    public DepartureOrArrival setIsExtra(boolean isExtra) {
        this.isExtra = isExtra;
        return this;
    }

    /**
     * Gets the vehicle calling at this stop.
     * @return the vehicle, or null for walking legs
     */
    public Vehicle getVehicle() {
        return this.vehicle;
    }

    /**
     * Sets the vehicle calling at this stop.
     * @param vehicle the vehicle to set, or null for walking legs
     * @return this instance for chaining
     */
    public DepartureOrArrival setVehicle(Vehicle vehicle) {
        this.vehicle = vehicle;
        return this;
    }

    /**
     * Generates a unique URI for this specific connection/departure.
     * @return the departure URI, or null for walking legs
     */
    public String getDepartureUri() {
        if (this.vehicle == null) {
            return null; // Not available on walking legs
        }
        String dateStr = this.scheduledDateTime.format(DateTimeFormatter.ofPattern("yyyyMMdd"));
        String vehicleName = this.vehicle.getName().replace(" ", "");
        return "http://irail.be/connections/" + this.station.getId().substring(2) + "/" +
            dateStr + "/" + vehicleName;
    }

    /**
     * Gets occupancy information for this stop.
     * @return the occupancy info, or UNKNOWN if not set
     */
    public OccupancyInfo getOccupancy() {
        return this.occupancy != null ? this.occupancy : new OccupancyInfo(OccupancyLevel.UNKNOWN, OccupancyLevel.UNKNOWN);
    }

    /**
     * Sets occupancy information for this stop.
     * @param occupancy the occupancy info to set
     */
    public void setOccupancy(OccupancyInfo occupancy) {
        this.occupancy = occupancy;
    }

    /**
     * Sets the state of this stop (approaching, halting, left).
     * @param status the status to set
     */
    public void setStatus(DepartureArrivalState status) {
        this.status = status;
    }

    /**
     * Gets the state of this stop.
     * @return the status
     */
    public DepartureArrivalState getStatus() {
        return this.status;
    }
}
