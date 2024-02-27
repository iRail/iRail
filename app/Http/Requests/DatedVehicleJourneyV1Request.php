<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;
use Irail\Exceptions\Request\InvalidRequestException;

class DatedVehicleJourneyV1Request extends IrailHttpRequest implements VehicleJourneyRequest
{
    use VehicleJourneyCacheId;

    private ?string $vehicleId, $datedJourneyId;
    private string $language;
    private Carbon $dateTime;

    /**
     * @param string|null $vehicleId
     * @param string|null $datedJourneyId
     * @param Carbon    $requestDateTime
     * @param string      $language
     * @throws InvalidRequestException
     */
    public function __construct()
    {
        parent::__construct();
        $this->vehicleId = $this->_request->get('id');
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