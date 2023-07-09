<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;
use DateTime;

class DatedVehicleJourneyV2Request extends IrailHttpRequest implements VehicleJourneyRequest
{
    use VehicleJourneyCacheId;

    private ?string $vehicleId, $datedJourneyId;
    private string $language;
    private Carbon $dateTime;

    /**
     * @param string|null $vehicleId
     * @param string|null $datedJourneyId
     * @param DateTime    $requestDateTime
     * @param string      $language
     */
    public function __construct()
    {
        parent::__construct();
        $this->vehicleId = $this->routeOrGet('id');
        $this->dateTime = $this->parseDateTime($this->routeOrGet('date'));
    }

    /**
     * @inheritDoc
     */
    public function getVehicleId(): ?string
    {
        return $this->vehicleId;
    }

    /**
     * @inheritDoc
     */
    public function getDatedJourneyId(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }
}