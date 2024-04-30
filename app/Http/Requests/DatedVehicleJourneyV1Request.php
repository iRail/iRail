<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

class DatedVehicleJourneyV1Request extends IrailHttpRequest implements VehicleJourneyRequest
{
    use VehicleJourneyCacheId;

    private ?string $vehicleId;
    private ?string $datedJourneyId;
    private string $language;
    private Carbon $dateTime;

    public function __construct()
    {
        parent::__construct();
        $this->vehicleId = $this->_request->get('id');
        if (str_starts_with($this->vehicleId, 'BE.NMBS.')) {
            // Reformat old style ids
            $this->vehicleId = substr($this->vehicleId, strlen('BE.NMBS.'));
        }
        $this->dateTime = $this->parseIrailV1DateTime();
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
    public function getDateTime(): Carbon
    {
        return $this->dateTime;
    }
}
