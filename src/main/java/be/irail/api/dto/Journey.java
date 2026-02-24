package be.irail.api.dto;

import java.util.ArrayList;
import java.util.List;

/**
 * Represents a complete journey from an origin to a destination.
 * A journey consists of one or more journey legs (e.g., trains or walking).
 */
public class Journey {
    private List<JourneyLeg> legs = new ArrayList<>();
    private List<String> notes = new ArrayList<>();
    private List<Message> serviceAlerts = new ArrayList<>();

    /**
     * Gets the list of legs that make up this journey.
     * @return the list of journey legs
     */
    public List<JourneyLeg> getLegs() {
        return this.legs;
    }

    /**
     * Gets the initial departure point of the entire journey.
     * @return the departure information of the first leg
     */
    public DepartureOrArrival getDeparture() {
        return this.legs.getFirst().getDeparture();
    }

    /**
     * Gets the final arrival point of the entire journey.
     * @return the arrival information of the last leg
     */
    public DepartureOrArrival getArrival() {
        return this.legs.getLast().getArrival();
    }

    /**
     * Sets the legs that make up this journey.
     * @param trainsInConnection the list of legs to set
     */
    public void setLegs(List<JourneyLeg> trainsInConnection) {
        this.legs = trainsInConnection;
    }

    /**
     * Calculates the total duration of the journey in seconds.
     * @return total duration in seconds
     */
    public long getDurationSeconds() {
        if (this.legs.isEmpty()) return 0;
        return this.legs.getFirst().getDeparture().getRealtimeDateTime()
            .until(this.legs.getLast().getArrival().getRealtimeDateTime(), java.time.temporal.ChronoUnit.SECONDS);
    }

    /**
     * Gets general notes or announcements related to this journey.
     * @return a list of notes
     */
    public List<String> getNotes() {
        return this.notes;
    }

    /**
     * Sets general notes for this journey.
     * @param notes the list of notes to set
     */
    public void setNotes(List<String> notes) {
        this.notes = notes;
    }

    /**
     * Gets service alerts that affect this journey.
     * @return a list of service alert messages
     */
    public List<Message> getServiceAlerts() {
        return this.serviceAlerts;
    }

    /**
     * Sets service alerts for this journey.
     * @param serviceAlerts the list of service alerts to set
     */
    public void setServiceAlerts(List<Message> serviceAlerts) {
        this.serviceAlerts = serviceAlerts;
    }
}
