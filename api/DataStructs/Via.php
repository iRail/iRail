<?php

/**
 * Description of Via
 *
 * @author pieterc
 */
class Via {
    private $vehicle;
    private $station;
    private $arrivalTime;
    private $arrivalPlatform;
    private $departTime;
    private $departPlatform;
    private $arrivalDelay;
    private $departDelay;
    
    function __construct($vehicle, $station, $arrivalTime, $arrivalPlatform, $departTime, $departPlatform, $arrivalDelay, $departDelay) {
        $this->vehicle = $vehicle;
        $this->station = $station;
        $this->arrivalTime = $arrivalTime;
        $this->arrivalPlatform = $arrivalPlatform;
        $this->departTime = $departTime;
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
        return $this->departTime - $this->arrivalTime;
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
