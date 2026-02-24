package be.irail.api.legacy.printer;

import be.irail.api.legacy.DataRoot;

import java.lang.reflect.Field;
import java.util.LinkedHashMap;
import java.util.Map;

/**
 * Prints the Json style output.
 */
public class V1JsonPrinter extends Printer {
    private String rootname;

    // Make a stack of array information, always work on the last one
    // for nested array support
    private final String[] stack = new String[100];
    private final int[] arrayIndices = new int[100];
    private int currentArrayIndex = -1;

    public V1JsonPrinter(DataRoot documentRoot) {
        super(documentRoot);
    }

    @Override
    public Map<String, String> getHeaders() {
        Map<String, String> headers = new LinkedHashMap<>();
        headers.put("Access-Control-Allow-Origin", "*");
        headers.put("Access-Control-Allow-Headers", "*");
        headers.put("Access-Control-Expose-Headers", "*");
        headers.put("Content-Type", "application/json;charset=UTF-8");
        return headers;
    }

    @Override
    public String getError(int errorCode, String message) {
        return "{\"error\":" + errorCode + ",\"message\":\"" + message + "\"}";
    }

    @Override
    public String startRootElement(String name, String version, String timestamp) {
        this.rootname = name;
        return "{\"version\":\"" + version + "\",\"timestamp\":\"" + timestamp + "\",";
    }

    @Override
    public String startArray(String name, int number, boolean root) {
        StringBuilder result = new StringBuilder();
        if (!root || "liveboard".equals(rootname) || "vehicleinformation".equals(rootname)) {
            result.append("\"").append(name).append("s\":{\"number\":\"").append(number).append("\",");
        }

        result.append("\"").append(name).append("\":[");

        currentArrayIndex++;
        stack[currentArrayIndex] = name;
        arrayIndices[currentArrayIndex] = 0;
        return result.toString();
    }

    @Override
    public String nextArrayElement() {
        arrayIndices[currentArrayIndex]++;
        return ",";
    }

    @Override
    public String nextObjectElement() {
        return ",";
    }

    @Override
    public String startObject(String name, Object object) {
        StringBuilder result = new StringBuilder();
        if (currentArrayIndex > -1 && name.equals(stack[currentArrayIndex])) {
            result.append("{");
            // Show id (in array) except if array of stations (compatibility issues)
            if (!"station".equals(name)) {
                result.append("\"id\":\"").append(arrayIndices[currentArrayIndex]).append("\",");
            }
        } else {
            String objectName = getObjectName(object);
            if (!"StationsDatasource".equals(rootname) && "station".equals(name) || "platform".equals(name)) {
                // split station and platform into station/platform and stationinfo/platforminfo,
                // to be compatible with 1.0
                result.append("\"").append(name).append("\":\"").append(objectName).append("\",");
                result.append("\"").append(name).append("info\":{");
            } else if (!"vehicle".equals(rootname) && "vehicle".equals(name)) {
                // split vehicle into vehicle and vehicleinfo to be compatible with 1.0
                result.append("\"").append(name).append("\":\"").append(objectName).append("\",");
                result.append("\"").append(name).append("info\":{");
            } else {
                result.append("\"").append(name).append("\":{");
            }
        }
        return result.toString();
    }

    private String getObjectName(Object object) {
        try {
            Field nameField = findField(object.getClass(), "name");
            if (nameField != null) {
                nameField.setAccessible(true);
                Object value = nameField.get(object);
                return value != null ? value.toString() : "";
            }
        } catch (Exception e) {
            // Ignore
        }
        return "";
    }

    private Field findField(Class<?> clazz, String fieldName) {
        while (clazz != null) {
            try {
                return clazz.getDeclaredField(fieldName);
            } catch (NoSuchFieldException e) {
                clazz = clazz.getSuperclass();
            }
        }
        return null;
    }

    @Override
    public String startKeyVal(String key, Object val) {
        if (key.equals("atId")){
            key = "@id";
        }
        String strVal = val != null ? val.toString() : "";
        // Escape special JSON characters
        strVal = strVal.replace("\\", "\\\\")
                .replace("\"", "\\\"")
                .replace("\n", "\\n")
                .replace("\r", "\\r")
                .replace("\t", "\\t");
        return "\"" + key + "\":\"" + strVal + "\"";
    }

    @Override
    public String endArray(String name, boolean root) {
        stack[currentArrayIndex] = "";
        arrayIndices[currentArrayIndex] = 0;
        currentArrayIndex--;

        if (root && !"liveboard".equals(rootname) && !"vehicleinformation".equals(rootname)) {
            return "]";
        } else {
            return "]}";
        }
    }

    @Override
    public String endObject(String name) {
        return "}";
    }

    @Override
    public String endElement(String name) {
        return "";
    }

    @Override
    public String endRootElement(String name) {
        return "}";
    }
}
