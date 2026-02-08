package be.irail.api.legacy.printer;

import be.irail.api.legacy.DataRoot;

import java.time.Instant;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Set;

/**
 * Prints the Xml style output.
 */
public class V1XmlPrinter extends Printer {
    private static final Set<String> ATTRIBUTES = Set.of(
            "id",
            "@id",
            "atId", // java representation of @id
            "locationX",
            "locationY",
            "standardname",
            "left",
            "arrived",
            "delay",
            "canceled",
            "partiallyCanceled",
            "normal",
            "shortname",
            "walking",
            "isExtraStop",
            "isExtra",
            "hafasId",
            "type",
            "number"
    );

    private static final Set<String> CDATA_ELEMENTS = Set.of(
            "header", "title", "description", "richtext", "link", "direction"
    );

    private static final Set<String> TIME_ELEMENTS = Set.of(
            "time", "startTime", "endTime", "departureTime", "arrivalTime",
            "scheduledDepartureTime", "scheduledArrivalTime"
    );

    private static final DateTimeFormatter ISO_FORMATTER = DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss")
            .withZone(ZoneId.of("Europe/Brussels"));

    private String rootname;

    // Make a stack of array information, always work on the last one
    // for nested array support
    private final String[] stack = new String[100];
    private final int[] arrayIndices = new int[100];
    private int currentArrayIndex = -1;

    public V1XmlPrinter(DataRoot documentRoot) {
        super(documentRoot);
    }

    @Override
    public Map<String, String> getHeaders() {
        Map<String, String> headers = new LinkedHashMap<>();
        headers.put("Access-Control-Allow-Origin", "*");
        headers.put("Access-Control-Allow-Headers", "*");
        headers.put("Access-Control-Expose-Headers", "*");
        headers.put("Content-Type", "application/xml;charset=UTF-8");
        return headers;
    }

    @Override
    public String getError(int errorCode, String message) {
        return "<error code=\"" + errorCode + "\">" + escapeXml(message) + "</error>";
    }

    @Override
    public String startRootElement(String name, String version, String timestamp) {
        this.rootname = name;
        return "<" + name + " version=\"" + version + "\" timestamp=\"" + timestamp + "\">";
    }

    @Override
    public String startArray(String name, int number, boolean root) {
        StringBuilder result = new StringBuilder();
        if (!root || "liveboard".equals(rootname) || "vehicleinformation".equals(rootname)) {
            result.append("<").append(name).append("s number=\"").append(number).append("\">");
        }

        currentArrayIndex++;
        arrayIndices[currentArrayIndex] = 0;
        stack[currentArrayIndex] = name;
        return result.toString();
    }

    @Override
    public String nextArrayElement() {
        arrayIndices[currentArrayIndex]++;
        return "";
    }

    @Override
    public String startObject(String name, Object object) {
        StringBuilder result = new StringBuilder();
        result.append("<").append(name);

        // Test whether this object is a first-level array object
        if (currentArrayIndex > -1 && name.equals(stack[currentArrayIndex]) && !"station".equals(name)) {
            result.append(" id=\"").append(arrayIndices[currentArrayIndex]).append("\"");
        }

        // Fallback for attributes and name tag
        Map<String, Object> hash = getObjectFields(object);
        String named = "";

        for (Map.Entry<String, Object> entry : hash.entrySet()) {
            String elementKey = entry.getKey();
            Object elementVal = entry.getValue();

            if (elementVal == null) continue;

            if (ATTRIBUTES.contains(elementKey)) {
                String attrName = "@id".equals(elementKey) || "atId".equals(elementKey) ? "URI" : elementKey;
                Object attrVal = elementVal;
                if ("normal".equals(elementKey) || "canceled".equals(elementKey)) {
                    attrVal = (elementVal instanceof Boolean && (Boolean) elementVal) ? 1 : 0;
                }
                result.append(" ").append(attrName).append("=\"").append(attrVal).append("\"");
            } else if ("name".equals(elementKey)) {
                named = elementVal.toString();
            }
        }

        result.append(">");

        if (!named.isEmpty()) {
            if (isCdataElement(name)) {
                result.append("<![CDATA[");
            }
            result.append(named);
        }
        return result.toString();
    }

    @Override
    public String startKeyVal(String key, Object val) {
        StringBuilder result = new StringBuilder();
        String strVal = val != null ? val.toString() : "";

        if (TIME_ELEMENTS.contains(key)) {
            String formatted = iso8601(strVal);
            result.append("<").append(key).append(" formatted=\"").append(formatted).append("\">").append(strVal);
        } else if (!"name".equals(key) && !ATTRIBUTES.contains(key)) {
            result.append("<").append(key).append(">");
            if (isCdataElement(key)) {
                result.append("<![CDATA[");
            }
            result.append(escapeXml(strVal));
        }
        return result.toString();
    }

    @Override
    public String endElement(String key) {
        StringBuilder result = new StringBuilder();
        if (isCdataElement(key)) {
            result.append("]]>");
        }

        if (!ATTRIBUTES.contains(key) && !"name".equals(key)) {
            result.append("</").append(key).append(">");
        }
        return result.toString();
    }

    @Override
    public String endArray(String name, boolean root) {
        StringBuilder result = new StringBuilder();
        if (!root || "liveboard".equals(rootname) || "vehicleinformation".equals(rootname)) {
            result.append("</").append(name).append("s>");
        }
        stack[currentArrayIndex] = "";
        arrayIndices[currentArrayIndex] = 0;
        currentArrayIndex--;
        return result.toString();
    }

    @Override
    public String endRootElement(String name) {
        return "</" + name + ">";
    }

    /**
     * Convert unix timestamp to ISO 8601 format in Europe/Brussels timezone.
     */
    private String iso8601(String unixtime) {
        try {
            long timestamp = Long.parseLong(unixtime);
            return ISO_FORMATTER.format(Instant.ofEpochSecond(timestamp));
        } catch (NumberFormatException e) {
            return unixtime;
        }
    }

    private boolean isCdataElement(String name) {
        return CDATA_ELEMENTS.contains(name);
    }

    private String escapeXml(String str) {
        if (str == null) return "";
        return str.replace("&", "&amp;")
                .replace("<", "&lt;")
                .replace(">", "&gt;")
                .replace("\"", "&quot;")
                .replace("'", "&apos;");
    }
}
