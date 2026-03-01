package be.irail.api.util;

import be.irail.api.exception.InternalProcessingException;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

/**
 * Utility class for parsing and extracting information from vehicle identifiers.
 * Handles Belgian train numbering schemes, including S-trains and buses.
 */
public class VehicleIdTools {
    private static final Logger log = LogManager.getLogger(VehicleIdTools.class);

    /**
     * Extracts the numeric train number from a full vehicle ID.
     * For example, "S104522" will return "4522".
     *
     * @param vehicleId the full vehicle ID (e.g., "IC 548", "S104522")
     * @return the extracted train number as a string
     */
    public static int extractTrainNumber(String vehicleId) {
        String id = extractTrainNumberString(vehicleId);
        int result = 0;
        try {
            result = Integer.parseInt(id);
        } catch (NumberFormatException e) {
            log.error("Failed to extract journey number for vehicle " + vehicleId);
            throw new InternalProcessingException("Failed to extract journey number for vehicle " + vehicleId);
        }
        return result;
    }

    private static String extractTrainNumberString(String vehicleId) {
        if (vehicleId == null) {
            return null;
        }
        String id = vehicleId.toUpperCase();
        // Handle S trains. For example, S5 3381 or S53381 should become 3381. Typically a number has 4 digits.
        id = id.replaceAll("BUS ?(\\d{3,5})", "$1"); // BUSS123, BUSS23456
        id = id.replaceAll("S[12]0 ?(\\d{4})", "$1"); // S10, S20
        id = id.replaceAll("S19 ?(\\d{4})", "$1"); // S19
        id = id.replaceAll("S32 ?(4\\d{2})", "$1"); // S32 400
        id = id.replaceAll("S3[2345] ?(\\d{4})", "$1"); // S32, S33, S34
        id = id.replaceAll("S4[1234] ?(\\d{4})", "$1"); // S41, 42, 43, 44
        id = id.replaceAll("S51 ?(\\d{3,4})", "$1"); // S51
        id = id.replaceAll("S52 ?(\\d{4})", "$1"); // S51, 52, 53
        id = id.replaceAll("S53 ?(6\\d{2})", "$1"); // S53 650, 651, ...
        id = id.replaceAll("S6[1234] ?(\\d{4})", "$1"); // S61, 62, 63, 64
        id = id.replaceAll("S81 ?([78]\\d{4})", "$1"); // S81 7xxx or 8xxx
        id = id.replaceAll("S[0-9] ?", ""); // S1-S9
        id = id.replaceAll("[^0-9]", "");
        return id;
    }

    /**
     * Extracts the train type from a full vehicle ID.
     * For example, "S104522" will return "S10".
     *
     * @param vehicleId the full vehicle ID
     * @return the extracted train type (e.g., "IC", "S10")
     */
    public static String extractTrainType(String vehicleId) {
        if (vehicleId == null) {
            return null;
        }
        String number = extractTrainNumberString(vehicleId);
        return vehicleId.substring(0, vehicleId.length() - number.length()).trim();
    }
}
