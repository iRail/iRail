<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * LiveboardRequest Class.
 *
 * @author pieterc
 */
include_once 'Request.php';
include_once 'data/NMBS/stations.php';

class LiveboardRequest extends Request
{
    protected $station;
    protected $date;
    protected $time;
    protected $arrdep;
    protected $fast;
    protected $alerts;

    public function __construct()
    {
        parent::__construct();
        parent::setGetVar('station', '');
        parent::setGetVar('date', date('dmy'));
        parent::setGetVar('arrdep', 'DEP');
        parent::setGetVar('time', date('Hi'));
        parent::setGetVar('fast', 'false');
        parent::setGetVar('alerts', 'false');

        // reform date and time to wanted structure for hafas and railtime (Ymd)
        if (strlen($this->date) == 6) {
            $y = '20' . substr($this->date, 4, 2);
        } elseif (strlen($this->date) == 4) {
            $y = date('Y');
        } else {
            throw new Exception("Invalid date supplied! Date should be in a ddmmyy or ddmm format.",400);
        }

        $d = substr($this->date, 0, 2);
        $m = substr($this->date, 2, 2);
        if ($d > 31 || $m > 12) {
            throw new Exception("Invalid date supplied! Date should be in a ddmmyy or ddmm format.",400);
        }

        // Store as Ymd
        $this->date = $y . $m . $d;

        preg_match('/(..)(..)/si', $this->time, $m);
        $this->time = $m[1].':'.$m[2];

        if ($this->station == '' && isset($_GET['id'])) {
            //then there was an id given
            $this->station = $_GET['id'];
        }

        parent::processRequiredVars(['station']);
    }

    /**
     * @return bool
     */
    public function isFast()
    {
        return $this->fast == 'true';
    }

    /**
     * @return bool
     */
    public function getAlerts()
    {
        return $this->alerts == 'true';
    }

    /**
     * @return mixed
     */
    public function getStation()
    {
        return $this->station;
    }

    /**
     * @param $station a resolved station object
     */
    public function setStation($station)
    {
        $station = stations::transformOldToNewStyle($station);
        $station['query'] = $this->station;
        $this->station = $station;
    }
    
    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return mixed
     */
    public function getArrdep()
    {
        return $this->arrdep;
    }
}
