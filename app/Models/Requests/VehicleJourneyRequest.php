<?php

namespace Irail\Models\Requests;

use DateTime;

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
     * @return DateTime
     */
    public function getDateTime(): DateTime;

    /**
     * Get the requested response language, as an ISO2 code.
     * @return string
     */
    public function getLanguage(): string;
}