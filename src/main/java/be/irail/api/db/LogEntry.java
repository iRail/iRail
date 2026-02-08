package be.irail.api.db;

import java.time.OffsetDateTime;
import java.util.Map;

/**
 * Represents a log entry for an API request.
 * Contains details about the request type, parameters, result summary, and client metadata.
 */
public class LogEntry {
    private final int id;
    private final LogQueryType queryType;
    private final Map<String, Object> query;
    private final Map<String, Object> result;
    private final String userAgent;
    private final OffsetDateTime createdAt;

    /**
     * Constructs a new LogEntry with all request details.
     *
     * @param id the unique log entry identifier
     * @param queryType the type of query performed
     * @param query the request parameters
     * @param result a summary of the result returned
     * @param userAgent the client's User-Agent string
     * @param createdAt the timestamp when the request occurred
     */
    public LogEntry(int id, LogQueryType queryType, Map<String, Object> query, Map<String, Object> result, String userAgent, OffsetDateTime createdAt) {
        this.id = id;
        this.queryType = queryType;
        this.query = query;
        this.result = result;
        this.userAgent = userAgent;
        this.createdAt = createdAt;
    }

    /**
     * Gets the unique identifier for this log entry.
     * @return the log ID
     */
    public int getId() {
        return this.id;
    }

    /**
     * Gets the type of API query that was logged.
     * @return the query type
     */
    public LogQueryType getQueryType() {
        return this.queryType;
    }

    /**
     * Gets the parameters of the logged query.
     * @return a map of request parameters
     */
    public Map<String, Object> getQuery() {
        return this.query;
    }

    /**
     * Gets the summary of the result returned by the API.
     * @return a map describing the result
     */
    public Map<String, Object> getResult() {
        return this.result;
    }

    /**
     * Gets the client's User-Agent string.
     * @return the User-Agent
     */
    public String getUserAgent() {
        return this.userAgent;
    }

    /**
     * Gets the timestamp when this log entry was created.
     * @return the creation timestamp
     */
    public OffsetDateTime getCreatedAt() {
        return this.createdAt;
    }
}
