package be.irail.api.riv.requests;

import be.irail.api.dto.Language;

public record ServiceAlertsRequest(
    Language language
) implements RivRequest {
    @Override
    public String getCacheId() {
        return "ServiceAlerts-" + language.getValue();
    }
}
