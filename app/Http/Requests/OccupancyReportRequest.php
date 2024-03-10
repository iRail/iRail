<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;
use Irail\Models\OccupancyLevel;

/**
 * A POST request reporting occupancy.
 *
 * POST
 *
 * {
 *  "connection": "http://irail.be/connections/8871308/20160722/IC4516",
 *  "from": "http://irail.be/stations/NMBS/008871308",
 *  "date": "20160722",
 *  "vehicle": "http://irail.be/vehicle/IC4516",
 *  "occupancy": "http://api.irail.be/terms/low"
 * }
 */
class OccupancyReportRequest extends IrailHttpRequest
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return string the connection uri, for example "http://irail.be/connections/8871308/20160722/IC4516".
     */
    public function getConnectionUri(): string
    {
        return $this->json()->get('connection');
    }

    /**
     * @return string the station uri, for example "http://irail.be/stations/NMBS/008871308".
     */
    public function getFromStationUri(): string
    {
        $json = $this->json();
        if (!$json->has('vehicle')) {
            abort(400, 'Missing from parameter');
        }
        return $json->get('from');
    }

    /**
     * @return Carbon The date.
     */
    public function getDate(): Carbon
    {
        $json = $this->json();
        if (!$json->has('vehicle')) {
            abort(400, 'Missing date parameter');
        }
        return Carbon::createFromFormat('YYYYmmdd', $json->get('date'));
    }

    /**
     * @return string The vehicle uri, for example "http://irail.be/vehicle/IC4516".
     */
    public function getVehicleUri(): string
    {
        $json = $this->json();
        if (!$json->has('vehicle')) {
            abort(400, 'Missing vehicle parameter');
        }
        return $json->get('vehicle');
    }

    /**
     * @return OccupancyLevel The occupancy level.
     */
    public function getReportedOccupancy(): OccupancyLevel
    {
        $json = $this->json();
        if (!$json->has('occupancy')) {
            abort(400, 'Missing occupancy parameter');
        }
        return OccupancyLevel::fromUri($json->get('occupancy'));
    }
}
