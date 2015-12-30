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

    public function __construct()
    {
        parent::__construct();
        parent::setGetVar('id', '');
        parent::setGetVar('fast', 'false');
        parent::setGetVar('alerts', 'false');
        parent::processRequiredVars(['id']);
    }

    /**
     * @return mixed
     */
    public function getVehicleId()
    {
        return $this->id;
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
