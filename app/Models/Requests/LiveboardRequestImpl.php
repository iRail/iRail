<?php

namespace Irail\Models\Requests;

use DateTime;

class LiveboardRequestImpl implements LiveboardRequest
{
    use LiveboardCacheId;

    private string $language;
    private int $departureArrivalMode;
    private DateTime $dateTime;
    private string $stationId;

    /**
     * @param string   $language
     * @param int      $departureArrivalMode
     * @param DateTime $dateTime
     * @param string   $stationId
     */
    public function __construct(string $language, int $departureArrivalMode, DateTime $dateTime, string $stationId)
    {
        $this->language = $language;
        $this->departureArrivalMode = $departureArrivalMode;
        $this->dateTime = $dateTime;
        $this->stationId = $stationId;
    }

    /**
     * @return string
     */
    public function getStationId(): string
    {
        return $this->stationId;
    }

    /**
     * @return DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * @return int
     */
    public function getDepartureArrivalMode(): int
    {
        return $this->departureArrivalMode;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }
}