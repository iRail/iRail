package be.irail.api.riv.requests;

import be.irail.api.db.Station;
import be.irail.api.dto.Language;
import be.irail.api.dto.StationDto;
import be.irail.api.dto.TimeSelection;
import be.irail.api.riv.TypeOfTransportFilter;
import java.time.LocalDateTime;

public record JourneyPlanningRequest(
    Station from,
    Station to,
    LocalDateTime dateTime,
    TimeSelection timeSelection,
    TypeOfTransportFilter typesOfTransport,
    Language language
) implements RivRequest {
    public JourneyPlanningRequest(Station from, Station to, LocalDateTime dateTime, TimeSelection timeSelection, Language language) {
        this(from, to, dateTime, timeSelection, TypeOfTransportFilter.AUTOMATIC, language);
    }

    @Override
    public String getCacheId() {
        return "Journey-" + from.getIrailId() + "-" + to.getIrailId() + "-" + dateTime.toString() + "-" + timeSelection.name() + "-" + typesOfTransport.name();
    }
}
