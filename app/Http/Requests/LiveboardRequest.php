<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

interface LiveboardRequest extends CacheableRequest
{
    /**
     * @return string
     */
    public function getStationId(): string;

    /**
     * @return Carbon
     */
    public function getDateTime(): Carbon;

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
