package be.irail.api.dto;

import be.irail.api.util.VehicleIdTools;

import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.time.ZoneOffset;
import java.util.Objects;

/**
 * Represents a public transport vehicle, typically a train.
 * This model captures the vehicle's identity, type, and its current journey context.
 */
public class Vehicle {
    private final String uri;
    private final String type;
    private final int number;
    private final LocalDate journeyStartDate;
    private VehicleDirection direction;

    /**
     * Private constructor for Vehicle. Use factory methods instead.
     *
     * @param type             The type, for example IC, EUR, S10.
     * @param number           The journey number, for example 548 or 2078.
     * @param journeyStartDate The start date of the journey.
     */
    private Vehicle(String type, int number, LocalDate journeyStartDate) {
        this.uri = String.format("http://irail.be/vehicle/%s%d", type, number);
        this.type = type;
        this.number = number;
        this.journeyStartDate = Objects.requireNonNullElseGet(journeyStartDate, LocalDate::now);
    }

    /**
     * Creates a Vehicle from a type and journey number.
     *
     * @param type the vehicle type
     * @param number the journey number
     * @param journeyStartDate the start date of the journey
     * @return a new Vehicle instance
     */
    public static Vehicle fromTypeAndNumber(String type, int number, LocalDate journeyStartDate) {
        return new Vehicle(type, number, journeyStartDate);
    }

    /**
     * Creates a Vehicle from its full name (e.g., "IC 548").
     *
     * @param name the vehicle name
     * @param journeyStartDate the start date of the journey
     * @return a new Vehicle instance
     */
    public static Vehicle fromName(String name, LocalDate journeyStartDate) {
        return Vehicle.fromTypeAndNumber(
            VehicleIdTools.extractTrainType(name),
            VehicleIdTools.extractTrainNumber(name),
            journeyStartDate
        );
    }

    /**
     * Gets the URI representing the vehicle journey.
     * @return the vehicle URI
     */
    public String getUri() {
        return this.uri;
    }

    /**
     * Gets the unique identifier for the vehicle journey (type + number).
     * @return the vehicle ID
     */
    public String getId() {
        return this.getType() + this.getNumber();
    }

    /**
     * Gets the type of the vehicle (e.g., IC, L, S).
     * @return the vehicle type
     */
    public String getType() {
        return this.type;
    }

    /**
     * Gets the journey number of the vehicle.
     * @return the journey number
     */
    public int getNumber() {
        return this.number;
    }

    /**
     * Gets the human-readable name of the vehicle (type + " " + number).
     * @return the vehicle name
     */
    public String getName() {
        return this.type + " " + this.number;
    }

    /**
     * Gets the current direction/destination of the vehicle.
     * @return the vehicle direction
     */
    public VehicleDirection getDirection() {
        return this.direction;
    }

    /**
     * Sets the direction/destination of the vehicle.
     * @param direction the direction to set
     */
    public void setDirection(VehicleDirection direction) {
        this.direction = direction;
    }

    /**
     * Gets the start date of the vehicle's journey.
     * @return the journey start date
     */
    public LocalDate getJourneyStartDate() {
        return this.journeyStartDate;
    }
}
