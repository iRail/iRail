<?php

namespace Irail\Models;

class StationInfo
{
    private string $id;
    private string $uri;

    private string $stationName;
    private string $localizedStationName;

    private ?float $latitude;
    private ?float $longitude;

    /**
     * @param string     $id
     * @param string     $uri
     * @param string     $stationName
     * @param string     $localizedStationName
     * @param float|null $latitude
     * @param float|null $longitude
     */
    public function __construct(string $id, string $uri,
        string $stationName, string $localizedStationName,
        ?float $longitude = null, ?float $latitude = null)
    {
        $this->id = $id;
        $this->uri = $uri;
        $this->stationName = $stationName;
        $this->localizedStationName = $localizedStationName;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getStationName(): string
    {
        return $this->stationName;
    }

    /**
     * @return string
     */
    public function getLocalizedStationName(): string
    {
        return $this->localizedStationName;
    }

    /**
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function toResponseArray()
    {
        return [
            'id'            => $this->id,
            'uri'           => $this->uri,
            'name'          => $this->stationName,
            'localizedName' => $this->localizedStationName,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude
        ];
    }


}
