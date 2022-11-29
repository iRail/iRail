<?php

namespace Irail\Http\Requests;

use DateTime;

class VehicleJourneyRequestImpl implements VehicleJourneyRequest
{
    use VehicleJourneyCacheId;

    private ?string $vehicleId, $datedJourneyId;
    private string $language;
    private DateTime $requestDateTime;

    /**
     * @param string|null $vehicleId
     * @param string|null $datedJourneyId
     * @param DateTime    $requestDateTime
     * @param string      $language
     */
    public function __construct(?string $vehicleId, ?string $datedJourneyId, DateTime $requestDateTime, string $language)
    {
        $this->vehicleId = $vehicleId;
        $this->datedJourneyId = $datedJourneyId;
        $this->language = $language;
        $this->requestDateTime = $requestDateTime;
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
        return $this->datedJourneyId;
    }

    /**
     * @inheritDoc
     */
    public function getDateTime(): DateTime
    {
        return $this->requestDateTime;
    }

    /**
     * @inheritDoc
     */
    public function getLanguage(): string
    {
        return $this->language;
    }
}