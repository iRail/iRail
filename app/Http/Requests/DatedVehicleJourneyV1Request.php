<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
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

        try {
            $date = $this->_request->get('date') ?: date('Ymd');
            $time = $this->_request->get('time') ?: date('Hi');
            if (strlen($date) == 6) {
                $date = '20' . $date;
            }
            if (strlen($time) == 3) {
                $time = '0' . $time;
            }
            $this->dateTime = Carbon::createFromFormat('Ymd Hi', $date . ' ' . $time);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new InvalidRequestException('Invalid date/time provided');
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
    public function getDateTime(): Carbon
    {
        return $this->dateTime;
    }
}