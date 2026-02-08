package be.irail.api.legacy;

import be.irail.api.dto.Language;
import be.irail.api.dto.StationDto;

import java.util.Comparator;
import java.util.List;

/**
 * Converts a list of stations to V1 DataRoot format for legacy API compatibility.
 */
public class StationsV1Converter extends V1Converter {

    /**
     * Converts a list of stations to the V1 DataRoot format.
     *
     * @param stations the list of stations
     * @param language the language for sorting
     * @return the DataRoot for V1 output
     */
    public static DataRoot convert(List<StationDto> stations, Language language) {
        DataRoot result = new DataRoot("stations");
        
        // Sort stations alphabetically by localized name
        List<StationDto> sortedStations = stations.stream()
                .sorted(Comparator.comparing(StationDto::getLocalizedStationName))
                .toList();
        
        result.station = sortedStations.stream()
                .map(V1Converter::convertStation)
                .toArray();
        return result;
    }

}
