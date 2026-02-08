package be.irail.api.riv.requests;

import be.irail.api.db.Station;
import be.irail.api.dto.Language;
import be.irail.api.dto.StationDto;
import be.irail.api.dto.TimeSelection;
import java.time.LocalDateTime;

public record LiveboardRequest(
    Station station,
    LocalDateTime dateTime,
    TimeSelection timeSelection,
    Language language
) implements RivRequest {
    @Override
    public String getCacheId() {
        return "Liveboard-" + station.getIrailId() + "-" + dateTime.toString() + "-" + timeSelection.name();
    }
}
