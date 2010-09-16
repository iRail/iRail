<?php

/**
 * Description of Via
 *
 * @author pieterc
 */
class Via {
    private $vehicle;
    private $station;
    private $timeBetween;
    private $arrivalTime;
    private $arrivalPlatform;
    private $departTime;
    private $departDate;
    private $departPlatform;
    private $arrivalDelay;
    private $departDelay;
    
    function __construct($vehicle, $station, $timeBetween, $arrivalTime, $arrivalPlatform, $departTime, $departDate, $departPlatform, $arrivalDelay, $departDelay) {
        $this->vehicle = $vehicle;
        $this->station = $station;
        $this->timeBetween = $timeBetween;
        $this->arrivalTime = $arrivalTime;
        $this->arrivalPlatform = $arrivalPlatform;
        $this->departTime = $departTime;
        $this->departDate = $departDate;
        $this->departPlatform = $departPlatform;
        $this->arrivalDelay = $arrivalDelay;
        $this->departDelay = $departDelay;
    }


    public function getVehicle() {
        return $this->vehicle;
    }

    public function getStation() {
        return $this->station;
    }

    public function getTimeBetween() {
        return $this->timeBetween;
    }

    public function getArrivalTime() {
        return $this->arrivalTime;
    }

    public function getArrivalPlatform() {
        return $this->arrivalPlatform;
    }

    public function getDepartTime() {
        return $this->departTime;
    }

    public function getDepartDate() {
        return $this->departDate;
    }

    public function getDepartPlatform() {
        return $this->departPlatform;
    }

    public function getArrivalDelay() {
        return $this->arrivalDelay;
    }

    public function getDepartDelay() {
        return $this->departDelay;
    }



}
?>
