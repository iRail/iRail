<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * The request information for a vehicle lookup. It tracks the location and gets information about its future location.
 *
 * @author pieterc
 */
include_once 'Request.php';

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

        // reform date and time to wanted structure for hafas and railtime
        preg_match('/(..)(..)(..)/si', $this->date, $m);

        if (count($m) > 3) {
            $this->date = $m[1] . $m[2] . $m[3];
            if ($m[2] > 12 || $m[1] > 31 || $m[2] == 0 || $m[1] == 0) {
                throw new Exception("Invalid date supplied! Date should be in a ddmmyy or ddmm format.", 400);
            }
        } elseif (count($m) > 2) {
            $this->date = $m[1] . $m[2] .  date('y');
            if ($m[2] > 12 || $m[1] > 31 || $m[2] == 0 || $m[1] == 0) {
                throw new Exception("Invalid date supplied! Date should be in a ddmmyy or ddmm format.", 400);
            }
        } else {
            throw new Exception("Invalid date supplied! Date should be in a ddmmyy or ddmm format.", 400);
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
