package be.irail.api.dto.result;

import be.irail.api.dto.Journey;
import be.irail.api.dto.StationDto;
import java.util.List;

/**
 * Result of a journey planning search between an origin and destination station.
 * Contains the list of found journeys and copies of the origin/destination stations.
 */
public class JourneyPlanningSearchResult {
    private StationDto originStation;
    private StationDto destinationStation;
    private List<Journey> journeys;

    /**
     * Gets the starting station of the planned journeys.
     * @return the origin station
     */
    public StationDto getOriginStation() {
        return originStation;
    }

    /**
     * Sets the starting station.
     * @param originStation the origin station to set
     */
    public void setOriginStation(StationDto originStation) {
        this.originStation = originStation;
    }

    /**
     * Gets the target station of the planned journeys.
     * @return the destination station
     */
    public StationDto getDestinationStation() {
        return destinationStation;
    }

    /**
     * Sets the target station.
     * @param destinationStation the destination station to set
     */
    public void setDestinationStation(StationDto destinationStation) {
        this.destinationStation = destinationStation;
    }

    /**
     * Gets the list of possible journeys found between the stations.
     * @return the list of journeys
     */
    public List<Journey> getJourneys() {
        return journeys;
    }

    /**
     * Sets the list of journeys.
     * @param journeys the list of journeys to set
     */
    public void setJourneys(List<Journey> journeys) {
        this.journeys = journeys;
    }
}
