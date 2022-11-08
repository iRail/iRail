<?php

/**
 * This is the data structure for a request. If we get more arguments, we will be able to add those here.
 *
 * @author pieterc
 */

namespace Irail\Models\Requests;

use DateTime;
use Irail\Data\Nmbs\Models\Station;
use Irail\Data\Nmbs\StationsDatasource;
use Irail\Models\Requests\CacheableRequest;
use Irail\Models\Requests\TimeSelection;

interface ConnectionsRequest extends CacheableRequest
{
    public function getOriginStationId(): string;

    public function getDestinationStationId(): string;

    public function getDateTime(): DateTime;

    public function getTimeSelection(): TimeSelection;

    public function getTypesOfTransport(): TypeOfTransportFilter;

    public function getLanguage(): string;
}
