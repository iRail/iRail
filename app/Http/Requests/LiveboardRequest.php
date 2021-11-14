<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * LiveboardRequest Class.
 *
 * @author pieterc
 */

namespace Irail\Http\Requests;

use Irail\Data\Nmbs\Models\Station;
use Irail\Data\Nmbs\StationsDatasource;

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

        preg_match('/(..)(..)(..)/si', $this->date, $m);

        if (count($m) > 3) {
            $this->date = '20' . $m[3] . $m[2] . $m[1];
        } elseif (count($m) > 2) {
            $this->date = date('Y') . $m[2] . $m[1];
        }

        preg_match('/(..)(..)/si', $this->time, $m);
        $this->time = $m[1] . ':' . $m[2];

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
     * @param $station Station resolved station object
     */
    public function setStation($station)
    {
        $station = StationsDatasource::transformOldToNewStyle($station);
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
