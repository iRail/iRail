package be.irail.api.riv.requests;

import java.time.LocalDate;

public record JourneyDetailRequest(
    String ctxRecon,
    LocalDate date
) implements RivRequest {
    @Override
    public String getCacheId() {
        return "JourneyDetail-" + ctxRecon + "-" + (date != null ? date.toString() : "today");
    }
}
