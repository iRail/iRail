<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * The request information for a vehicle lookup. It tracks the location and gets information about its future location.
 *
 * @author pieterc
 */

namespace Irail\api\requests;

class VehicleinformationRequest extends Request
{
    protected $id;
    protected $fast;
    protected $alerts;

    public function __construct()
    {
        parent::__construct();
        parent::setGetVar('id', '');
        parent::setGetVar('date', date('dmy'));
        parent::setGetVar('fast', 'false');
        parent::setGetVar('alerts', 'false');
        parent::processRequiredVars(['id']);

        preg_match('/(..)(..)(..)/si', $this->date, $m);

        if (count($m) > 3) {
            $this->date = '20'.$m[3].$m[2].$m[1];
        } elseif (count($m) > 2) {
            $this->date = date('Y').$m[2].$m[1];
        }

        // Ensure consistent ids from here on
        if (strpos($this->id, 'BE.NMBS.') === false) {
            $this->id = 'BE.NMBS.' . strtoupper($this->id);
        }

        if (strlen($this->id) > 24) {
            throw new Exception("Invalid vehicle id! The id parameter should be 2 to 24 characters long");
        }
    }

    /**
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
    public function getFast()
    {
        return $this->fast;
    }

    /**
     * @return mixed
     */
    public function getAlerts()
    {
        return $this->alerts == 'true';
    }
}
