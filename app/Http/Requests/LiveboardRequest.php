<?php

namespace Irail\Http\Requests;

use DateTime;

interface LiveboardRequest extends CacheableRequest
{
    /**
     * @return string
     */
    public function getStationId(): string;

    /**
     * @return DateTime
     */
    public function getDateTime(): DateTime;

    /**
     * @return TimeSelection
     */
    public function getDepartureArrivalMode(): TimeSelection;

    /**
     * Get the requested response language, as an ISO2 code.
     * @return string
     */
    public function getLanguage(): string;
}