<?php

namespace Irail\Models\Dao;

class CompositionStatistics
{
    private int $numberOfRecords;
    private int $medianProbableLength;
    private int $mostProbableLength;
    private int $mostProbableLengthOccurrence;
    private ?string $mostProbableType;
    private int $mostProbableTypeOccurrence;

    /**
     * @param int $numberOfRecords
     * @param int $medianProbableLength
     * @param int $mostProbableLength
     * @param int $mostProbableLengthOccurrence
     * @param string $mostProbableType
     * @param int $mostProbableTypeOccurrence
     */
    public function __construct(
        int $numberOfRecords,
        int $medianProbableLength,
        int $mostProbableLength,
        int $mostProbableLengthOccurrence,
        ?string $mostProbableType,
        int $mostProbableTypeOccurrence
    ) {
        $this->numberOfRecords = $numberOfRecords;
        $this->medianProbableLength = $medianProbableLength;
        $this->mostProbableLength = $mostProbableLength;
        $this->mostProbableLengthOccurrence = $mostProbableLengthOccurrence;
        $this->mostProbableType = $mostProbableType;
        $this->mostProbableTypeOccurrence = $mostProbableTypeOccurrence;
    }

    public function getNumberOfRecords(): int
    {
        return $this->numberOfRecords;
    }

    public function getMedianProbableLength(): int
    {
        return $this->medianProbableLength;
    }

    public function getMostProbableLength(): int
    {
        return $this->mostProbableLength;
    }

    public function getMostProbableLengthOccurrence(): int
    {
        return $this->mostProbableLengthOccurrence;
    }

    public function getMostProbableType(): ?string
    {
        return $this->mostProbableType;
    }

    public function getMostProbableTypeOccurrence(): int
    {
        return $this->mostProbableTypeOccurrence;
    }

    public function getFromStationId(): string
    {
        return '0'; // TODO: implement
    }

    public function getToStationId(): string
    {
        return '0'; // TODO: implement
    }
}