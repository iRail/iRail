<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * LiveboardRequest Class.
 *
 * @author pieterc
 */

namespace Irail\Http\Requests;

use DateTime;
use Exception;
use Irail\Models\DepartureArrivalMode;
use Irail\Models\Requests\LiveboardCacheId;
use Irail\Models\Requests\LiveboardRequest;

class LiveboardHttpRequest extends HttpRequest implements LiveboardRequest
{
    use LiveboardCacheId;

    private string $stationId;
    private DateTime $dateTime;
    private int $departureArrivalMode;

    /**
     * @throws Exception when the request is incomplete or invalid
     */
    public function __construct()
    {
        parent::__construct();
        parent::verifyRequiredVariablesPresent(['station']);

        $this->dateTime = $this->parseDateTime($this->get('datetime', 'now'));
        $this->stationId = $this->parseStationId($this->get('station'));
        $this->departureArrivalMode = $this->get('searchArrivals', false)
            ? DepartureArrivalMode::MODE_ARRIVAL
            : DepartureArrivalMode::MODE_DEPARTURE;
    }

    /**
     * @return string
     */
    public function getStationId(): string
    {
        return $this->stationId;
    }

    /**
     * @return DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * @return int
     */
    public function getDepartureArrivalMode(): int
    {
        return $this->departureArrivalMode;
    }

}
