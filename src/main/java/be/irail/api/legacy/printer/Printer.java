package be.irail.api.legacy.printer;

import be.irail.api.legacy.DataRoot;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.lang.reflect.Field;
import java.util.Collection;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Objects;

/**
 * An abstract class for a printer. It prints a document.
 */
public abstract class Printer {

    private static final Logger log = LogManager.getLogger(Printer.class);

    protected static final String PRIVATE_VAR_PREFIX = "_";
    protected DataRoot documentRoot;
    protected boolean root;

    public Printer(DataRoot documentRoot) {
        this.documentRoot = documentRoot;
    }

    /**
     * Prints the body: The idea behind this is a reversed sax-parser.
     * It will create events which you will have to implement in your implementation of an output.
     */
    public String getBody() throws Exception {
        StringBuilder result = new StringBuilder();
        Map<String, Object> hash = getObjectFields(documentRoot);

        root = true;
        result.append(startRootElement(
                documentRoot.getRootName().toLowerCase(),
                documentRoot.version,
                documentRoot.timestamp
        ));

        boolean hasWrittenElement = false;
        for (Map.Entry<String, Object> entry : hash.entrySet()) {
            String key = entry.getKey();
            Object val = entry.getValue();

            if (val == null) {
                continue;
            }

            if ("version".equals(key) || "timestamp".equals(key) || isPrivateVariableName(key)) {
                continue;
            }

            if (hasWrittenElement) {
                result.append(nextObjectElement());
            }

            result.append(printElement(key, val, true));
            hasWrittenElement = true;
        }
        result.append(endRootElement(documentRoot.getRootName().toLowerCase()));
        return result.toString();
    }

    /**
     * It will detect what kind of element the element is and will print it accordingly.
     * If it contains more elements it will print more recursively.
     */
    protected String printElement(String key, Object val, boolean root) throws Exception {
        StringBuilder result = new StringBuilder();

        if (val instanceof Collection<?> collection) {
            result.append(startArray(key, collection.size(), root));
            int i = 0;
            for (Object elementVal : collection) {
                result.append(printElement(key, elementVal, false));
                if (i < collection.size() - 1) {
                    result.append(nextArrayElement());
                }
                i++;
            }
            result.append(endArray(key, root));
        } else if (val != null && val.getClass().isArray()) {
            Object[] array = (Object[]) val;
            result.append(startArray(key, array.length, root));
            for (int i = 0; i < array.length; i++) {
                if (array[i] != null) {
                    result.append(printElement(key, array[i], false));
                    if (i < array.length - 1) {
                        result.append(nextArrayElement());
                    }
                }
            }
            result.append(endArray(key, root));
        } else if (val != null && !isPrimitive(val)) {
            result.append(startObject(key, val));
            Map<String, Object> allObjectVars = getObjectFields(val);

            // Remove all keys that won't be printed
            allObjectVars.entrySet().removeIf(entry ->
                    isPrivateVariableName(entry.getKey()) || entry.getValue() == null);

            int counter = 0;
            int totalFields = allObjectVars.size();
            for (Map.Entry<String, Object> entry : allObjectVars.entrySet()) {
                result.append(printElement(entry.getKey(), entry.getValue(), false));
                if (counter < totalFields - 1) {
                    result.append(nextObjectElement());
                }
                counter++;
            }
            result.append(endObject(key));
        } else if (val instanceof Boolean boolVal) {
            int intVal = boolVal ? 1 : 0;
            result.append(startKeyVal(key, intVal));
            result.append(endElement(key));
        } else if (val != null) {
            result.append(startKeyVal(key, val));
            result.append(endElement(key));
        } else if (val == null) {
            // Do nothing
        } else {
            throw new Exception(
                    "Could not serialize the data correctly - please report this problem to https://github.com/irail/irail. Key/Val: " + key + "/" + val
            );
        }
        return result.toString();
    }

    protected String printElement(String key, Object val) throws Exception {
        return printElement(key, val, false);
    }

    private boolean isPrimitive(Object val) {
        return val instanceof String || val instanceof Number || val instanceof Boolean;
    }

    protected Map<String, Object> getObjectFields(Object obj) {
        Map<String, Object> fields = new LinkedHashMap<>();
        Class<?> clazz = obj.getClass();

        while (clazz != null) {
            for (Field field : clazz.getDeclaredFields()) {
                field.setAccessible(true);
                try {
                    fields.put(field.getName(), field.get(obj));
                } catch (IllegalAccessException e) {
                    log.debug("Failed to retrieve field value while serializing v1 response", e);
                }
            }
            clazz = clazz.getSuperclass();
        }
        return fields;
    }

    public String nextArrayElement() {
        return "";
    }

    public String nextObjectElement() {
        return "";
    }

    protected boolean isPrivateVariableName(String elementKey) {
        return elementKey.startsWith(PRIVATE_VAR_PREFIX);
    }

    public abstract String startRootElement(String name, String version, String timestamp);

    public abstract String startArray(String name, int number, boolean root);

    public abstract String startObject(String name, Object object);

    public abstract String startKeyVal(String key, Object val);

    public abstract String endArray(String name, boolean root);

    public String endObject(String name) {
        return endElement(name);
    }

    public abstract String endElement(String name);

    public abstract String endRootElement(String name);

    public abstract String getError(int errorCode, String message);

    public abstract Map<String, String> getHeaders();
}
