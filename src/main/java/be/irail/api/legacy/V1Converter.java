package be.irail.api.legacy;

import be.irail.api.dto.*;

public abstract class V1Converter {

    protected static V1Station convertStation(StationDto station) {
        V1Station obj = new V1Station();
        obj.atId = station.getUri();
        obj.id = "BE.NMBS." + station.getId();
        obj.name = station.getLocalizedStationName();
        obj.locationX = station.getLongitude() != null ? String.valueOf(station.getLongitude()) : "0";
        obj.locationY = station.getLatitude() != null ? String.valueOf(station.getLatitude()) : "0";
        obj.standardname = station.getStationName();
        return obj;
    }

    protected static V1Platform convertPlatform(PlatformInfo platform) {
        V1Platform result = new V1Platform();
        result.name = platform != null ? platform.getDesignation() : "?";
        result.name = result.name == null ? "?" : result.name;
        result.normal = (platform == null || !platform.hasChanged()) ? "1" : "0";
        return result;
    }

    protected static V1Vehicle convertVehicle(Vehicle vehicle, StationDto lastVisitedStop) {
        V1Vehicle result = new V1Vehicle();
        result.name = "BE.NMBS." + vehicle.getType() + vehicle.getNumber();
        result.shortname = vehicle.getType() + " " + vehicle.getNumber();
        result.number = String.valueOf(vehicle.getNumber());
        result.type = vehicle.getType();
        result.locationX = lastVisitedStop != null ? String.valueOf(lastVisitedStop.getLongitude()) : "0";
        result.locationY = lastVisitedStop != null ? String.valueOf(lastVisitedStop.getLatitude()) : "0";
        result.atId = vehicle.getUri();
        return result;
    }

    protected static V1Occupancy convertOccupancy(OccupancyInfo occupancy) {
        if (occupancy == null) {
            V1Occupancy result = new V1Occupancy();
            result.atId = OccupancyLevelDTO.UNKNOWN.getUri();
            result.name = "unknown";
            return result;
        }
        OccupancyLevelDTO level = occupancy.getSpitsgidsLevel() != OccupancyLevelDTO.UNKNOWN
                ? occupancy.getSpitsgidsLevel()
                : occupancy.getOfficialLevel();
        V1Occupancy result = new V1Occupancy();
        result.atId = level.getUri();
        String uri = level.getUri();
        result.name = uri.contains("/") ? uri.substring(uri.lastIndexOf('/') + 1) : uri;
        return result;
    }

    public static class V1Station {
        public String atId;
        public String id;
        public String name;
        public String locationX;
        public String locationY;
        public String standardname;
    }

    public static class V1Platform {
        public String name;
        public String normal;
    }

    public static class V1Vehicle {
        public String name;
        public String shortname;
        public String number;
        public String type;
        public String locationX;
        public String locationY;
        public String atId;
    }

    public static class V1Occupancy {
        public String atId;
        public String name;
    }

    public static class V1Direction {
        public String name;
    }

    public static class V1Alert {
        public String header;
        public String description;
        public String lead;
        public long startTime;
        public long endTime;
    }
}
