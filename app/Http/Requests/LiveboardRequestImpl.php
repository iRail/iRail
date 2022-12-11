<?php

namespace Irail\Http\Requests;

use DateTime;

class LiveboardRequestImpl implements LiveboardRequest
{
    use LiveboardCacheId;

    private string $language;
    private TimeSelection $departureArrivalMode;
    private DateTime $dateTime;
    private string $stationId;

    /**
     * @param string        $stationId
     * @param TimeSelection $departureArrivalMode
     * @param string        $language
     * @param DateTime      $dateTime
     */
    public function __construct(string $stationId, TimeSelection $departureArrivalMode, string $language, DateTime $dateTime)
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
     * @return TimeSelection
     */
    public function getDepartureArrivalMode(): TimeSelection
    {
        return $this->departureArrivalMode;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }
}