package be.irail.api.riv;

import be.irail.api.db.Station;
import be.irail.api.dto.Language;
import be.irail.api.dto.StationDto;
import com.fasterxml.jackson.databind.JsonNode;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;

public abstract class RivClient {
    private static final Logger log = LogManager.getLogger(RivClient.class);

    protected StationDto convertToModelStation(Station dbStation, Language language) {
        if (dbStation == null) {
            return null;
        }
        return new StationDto(
                dbStation.getIrailId(),
                dbStation.getUri(),
                dbStation.getName(),
                dbStation.getName(language),
                dbStation.getLongitude(),
                dbStation.getLatitude()
        );
    }


    protected List<String> parseNotes(JsonNode tripNode) {
        List<String> notes = new ArrayList<>();
        if (tripNode.has("Notes") && tripNode.get("Notes").has("Note")) {
            for (JsonNode note : tripNode.get("Notes").get("Note")) {
                notes.add(note.get("value").asText());
            }
        }
        return notes;
    }


    protected LocalDate extractStartDate(String ref) {
        // Extract start date from ref
        String[] refParts = ref.split("\\|");
        LocalDate journeyStartDate = null;
        if (refParts.length >= 5) {
            // Parse the start date from "1|257447|0|80|1022026"
            if (refParts[4].length() == 7) {
                journeyStartDate = LocalDate.parse("0" + refParts[4], DateTimeFormatter.ofPattern("ddMMyyyy"));
            } else {
                journeyStartDate = LocalDate.parse(refParts[4], DateTimeFormatter.ofPattern("ddMMyyyy"));
            }
        } else {
            log.warn("Could not parse journey start date from ref: {}", ref);
        }
        return journeyStartDate;
    }
}
