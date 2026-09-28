package be.irail.api.util;

import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

/** Mints stable resource IRIs used by the public iRail API. */
public final class IrailUri {
    private static final String CONNECTION_BASE = "http://irail.be/connections/";
    private static final DateTimeFormatter DATE_FORMATTER = DateTimeFormatter.BASIC_ISO_DATE;

    private IrailUri() {
    }

    /**
     * Mints the IRI of a departure connection.
     *
     * <p>Station IDs exposed by the API contain the Belgian {@code 00} UIC
     * prefix, while connection IRIs have historically used the seven-digit
     * HAFAS identifier. Vehicle IDs are written without display whitespace.</p>
     */
    public static String connection(String stationId, LocalDate departureDate, String vehicleId) {
        String hafasId = stationId.startsWith("00") ? stationId.substring(2) : stationId;
        return CONNECTION_BASE + hafasId + "/" + DATE_FORMATTER.format(departureDate) + "/"
                + vehicleId.replace(" ", "");
    }
}
