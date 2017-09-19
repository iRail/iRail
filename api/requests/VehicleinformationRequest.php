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

        // store as dmy
        // reform date and time to wanted structure for hafas and railtime (Ymd)
        if (strlen($this->date) == 6) {
            $y = substr($this->date, 4, 2);
        } elseif (strlen($this->date) == 4) {
            $y = date('y');
        } else {
            throw new Exception("Invalid date supplied! Date should be in a ddmmyy or ddmm format.",400);
        }

        $d = substr($this->date, 0, 2);
        $m = substr($this->date, 2, 2);
        if ($d > 31 || $m > 12) {
            throw new Exception("Invalid date supplied! Date should be in a ddmmyy or ddmm format.",400);
        }

        // Store as Ymd
        $this->date = $d . $m . $y;
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
