package be.irail.api.legacy;

/**
 * This is the root of every document. It will specify a version and timestamp.
 * It also has the printer class to print the entire document.
 */
public class DataRoot {
    private final String _rootName;
    public String version;
    public String timestamp;
    
    // Dynamic fields for different response types
    public Object station;
    public Object departure;
    public Object arrival;
    public Object connection;
    public Object vehicle;
    public Object stop;
    public Object disturbance;
    public Object composition;

    public DataRoot(String rootName) {
        this._rootName = rootName;
        this.version = "1.4";
        this.timestamp = String.valueOf(System.currentTimeMillis() / 1000);
    }

    public String getRootName() {
        return _rootName;
    }
}
