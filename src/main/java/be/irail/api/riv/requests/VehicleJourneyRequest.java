package be.irail.api.riv.requests;

import be.irail.api.dto.Language;

import java.time.LocalDateTime;

public record VehicleJourneyRequest(
    String vehicleId,
    LocalDateTime dateTime,
    String ctxRecon,
    Language language
) implements RivRequest {
    
    public VehicleJourneyRequest(String vehicleId, LocalDateTime dateTime, String ctxRecon) {
        this(vehicleId, dateTime, ctxRecon, Language.EN);
    }
    
    @Override
    public String getCacheId() {
        return "Vehicle-" + vehicleId + "-" + (dateTime != null ? dateTime.toString() : "now") + (ctxRecon != null ? "-" + ctxRecon : "");
    }
}
