<?php

namespace Irail\Http\Requests;

use DateTime;

class LiveboardRequestImpl implements LiveboardRequest
{
    use LiveboardCacheId;

    private string $language;
    private int $departureArrivalMode;
    private DateTime $dateTime;
    private string $stationId;

    /**
     * @param string   $stationId
     * @param int      $departureArrivalMode
     * @param string   $language
     * @param DateTime $dateTime
     */
    public function __construct(string $stationId, int $departureArrivalMode, string $language, DateTime $dateTime)
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