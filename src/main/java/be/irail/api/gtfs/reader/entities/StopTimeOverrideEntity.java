package be.irail.api.gtfs.reader.entities;

import org.onebusaway.csv_entities.schema.annotations.CsvField;
import org.onebusaway.gtfs.model.IdentityBean;
import org.onebusaway.csv_entities.schema.annotations.CsvFields;

@CsvFields(filename = "stop_time_overrides.txt")
public class StopTimeOverrideEntity extends IdentityBean<Integer> {

    private static final long serialVersionUID = 1L;

    @CsvField(ignore = true)
    private int id;

    @CsvField(name = "trip_id")
    private String tripId;

    @CsvField(name = "service_id")
    private String serviceId;

    @CsvField(name = "stop_id")
    private String stopId;

    @CsvField(name = "stop_sequence")
    private int stopSequence;

    @Override
    public Integer getId() {
        return id;
    }

    @Override
    public void setId(Integer id) {
        this.id = id;
    }

    public String getTripId() {
        return tripId;
    }

    public void setTripId(String tripId) {
        this.tripId = tripId;
    }

    public String getServiceId() {
        return serviceId;
    }

    public void setServiceId(String serviceId) {
        this.serviceId = serviceId;
    }

    public String getStopId() {
        return stopId;
    }

    public void setStopId(String stopId) {
        this.stopId = stopId;
    }

    public int getStopSequence() {
        return stopSequence;
    }

    public void setStopSequence(int stopSequence) {
        this.stopSequence = stopSequence;
    }
}
