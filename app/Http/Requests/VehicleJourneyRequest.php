<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

interface VehicleJourneyRequest extends CacheableRequest
{
    /**
     * @return string|null
     */
    public function getVehicleId(): ?string;

    /**
     * @return string|null
     */
    public function getDatedJourneyId(): ?string;

    /**
     * @return Carbon
     */
    public function getDateTime(): Carbon;

    /**
     * Get the requested response language, as an ISO2 code.
     * @return string
     */
    public function getLanguage(): string;
}