<?php

namespace Irail\Models\Requests;

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
     * @return int
     */
    public function getDepartureArrivalMode(): int;

    /**
     * Get the requested response language, as an ISO2 code.
     * @return string
     */
    public function getLanguage(): string;
}