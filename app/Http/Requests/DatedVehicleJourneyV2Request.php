<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

class DatedVehicleJourneyV2Request extends IrailHttpRequest implements VehicleJourneyRequest
{
    use VehicleJourneyCacheId;

    private ?string $vehicleId;
    private ?string $datedJourneyId;
    private string $language;
    private ?Carbon $dateTime;

    public function __construct()
    {
        parent::__construct();
        $this->vehicleId = $this->routeOrGet('id');
        if ($this->routeOrGet('date')){
            $this->dateTime = $this->parseDateTime($this->routeOrGet('date'));
        } else {
            $this->dateTime = null;
        }

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
    public function getDateTime(): ?Carbon
    {
        return $this->dateTime;
    }
}
