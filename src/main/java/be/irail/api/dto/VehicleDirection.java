package be.irail.api.dto;

public class VehicleDirection {
    private StationDto station;
    private String name;

    public VehicleDirection(String name, StationDto station) {
        this.name = name;
        this.station = station;
    }

    /**
     * @return Station
     */
    public StationDto getStation() {
        return this.station;
    }

    /**
     * @param station
     */
    public void setStation(StationDto station) {
        this.station = station;
    }

    /**
     * @return String
     */
    public String getName() {
        return this.name;
    }

    /**
     * @param name
     */
    public void setName(String name) {
        this.name = name;
    }
}
