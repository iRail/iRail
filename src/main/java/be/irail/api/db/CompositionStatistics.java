package be.irail.api.db;

/**
 * Statistical data about historical train compositions for a specific journey.
 * Aggregates information like the most probable length and material type.
 */
public class CompositionStatistics {
    private final int numberOfRecords;
    private final int medianProbableLength;
    private final int mostProbableLength;
    private final int mostProbableLengthOccurrence;
    private final String mostProbableType;
    private final int mostProbableTypeOccurrence;

    /**
     * Constructs a new CompositionStatistics instance.
     *
     * @param numberOfRecords total number of records analyzed
     * @param medianProbableLength the median length across records
     * @param mostProbableLength the length that occurs most frequently
     * @param mostProbableLengthOccurrence frequency of the most probable length
     * @param mostProbableType the material type that occurs most frequently
     * @param mostProbableTypeOccurrence frequency of the most probable type
     */
    public CompositionStatistics(
        int numberOfRecords,
        int medianProbableLength,
        int mostProbableLength,
        int mostProbableLengthOccurrence,
        String mostProbableType,
        int mostProbableTypeOccurrence
    ) {
        this.numberOfRecords = numberOfRecords;
        this.medianProbableLength = medianProbableLength;
        this.mostProbableLength = mostProbableLength;
        this.mostProbableLengthOccurrence = mostProbableLengthOccurrence;
        this.mostProbableType = mostProbableType;
        this.mostProbableTypeOccurrence = mostProbableTypeOccurrence;
    }

    /**
     * Gets the total number of historical records found.
     * @return the number of records
     */
    public int getNumberOfRecords() {
        return this.numberOfRecords;
    }

    /**
     * Gets the median length found in historical data.
     * @return the median length
     */
    public int getMedianProbableLength() {
        return this.medianProbableLength;
    }

    /**
     * Gets the length that occurs most frequently.
     * @return the most probable length
     */
    public int getMostProbableLength() {
        return this.mostProbableLength;
    }

    /**
     * Gets how many times the most probable length was observed.
     * @return the occurrence count
     */
    public int getMostProbableLengthOccurrence() {
        return this.mostProbableLengthOccurrence;
    }

    /**
     * Gets the material type that occurs most frequently.
     * @return the most probable material type
     */
    public String getMostProbableType() {
        return this.mostProbableType;
    }

    /**
     * Gets how many times the most probable material type was observed.
     * @return the occurrence count
     */
    public int getMostProbableTypeOccurrence() {
        return this.mostProbableTypeOccurrence;
    }

    /**
     * Gets the starting station ID for these statistics.
     * @return the origin station ID
     */
    public String getFromStationId() {
        return "0"; // TODO: implement
    }

    /**
     * Gets the end station ID for these statistics.
     * @return the destination station ID
     */
    public String getToStationId() {
        return "0"; // TODO: implement
    }
}
