<?php

/**
 * This is the data structure for a request. If we get more arguments, we will be able to add those here.
 *
 * @author pieterc
 */

namespace Irail\Http\Requests;

use DateTime;

interface JourneyPlanningRequest extends CacheableRequest
{
    public function getOriginStationId(): string;

    public function getDestinationStationId(): string;

    public function getDateTime(): DateTime;

    public function getTimeSelection(): TimeSelection;

    public function getTypesOfTransport(): TypeOfTransportFilter;

    public function getLanguage(): string;
}