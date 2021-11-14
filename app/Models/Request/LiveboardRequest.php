<?php

namespace Irail\Models\Request;

use DateTime;

class LiveboardRequest implements CacheableRequest
{
    private string $stationId;
    private DateTime $dateTime;
    private DepartureArrivalMode $departureArrivalMode;
    private string $language;

    public function getCacheId(): string
    {
        return '\\NMBS\\Liveboard\\' . join('\\', [
                $this->stationId,
                $this->dateTime->getTimestamp(),
                $this->departureArrivalMode,
                $this->language
            ]);
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
     * @return DepartureArrivalMode
     */
    public function getDepartureArrivalMode(): DepartureArrivalMode
    {
        return $this->departureArrivalMode;
    }

    /**
     * @return string
     */
    public function getIso2Language(): string
    {
        return $this->language;
    }


}
