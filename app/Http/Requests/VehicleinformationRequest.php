<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * The request information for a vehicle lookup. It tracks the location and gets information about its future location.
 *
 * @author pieterc
 */

namespace Irail\Http\Requests;

use Exception;

class VehicleinformationRequest extends Request
{
    protected $id;
    protected $fast;
    protected $alerts;
    protected $date;

    /**
     * VehicleinformationRequest constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        parent::setGetVar('id', '');
        parent::setGetVar('date', date('dmy'));
        parent::setGetVar('alerts', 'false');
        parent::processRequiredVars(['id']);

        preg_match('/(..)(..)(..)/si', $this->date, $m);

        if (count($m) > 3) {
            $this->date = '20' . $m[3] . $m[2] . $m[1];
        } elseif (count($m) > 2) {
            $this->date = date('Y') . $m[2] . $m[1];
        }

        $this->cleanId();
    }

    /**
     * The requested vehicle id, for example IC 538
     * @return mixed
     */
    public function getVehicleId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getAlerts()
    {
        return $this->alerts == 'true';
    }

    /**
     * @throws Exception
     */
    private function cleanId(): void
    {
        $this->id = strtoupper($this->id);
        $this->id = trim(urldecode($this->id));
        // Ensure consistent ids from here on
        if (str_starts_with($this->id, 'BE.NMBS.')) {
            $this->id = substr($this->id, 8);
        }
        if (strlen($this->id) > 16) {
            throw new Exception("Invalid vehicle id! The id parameter should be 2 to 16 characters long", 400);
        }
    }
}
