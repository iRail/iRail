<?php
/**
 * Description of Routes
 *
 * @author pieterc
 */

class Route {
    private $stops = array();
    private $locationX = "";
    private $locationY = "";
    private $vehicle;
    
    function __construct($stops, $locationX, $locationY, $vehicle) {
        $this->stops = $stops;
        $this->locationX = $locationX;
        $this->locationY = $locationY;
        $this->vehicle = $vehicle;
    }

    public function getVehicle() {
        return $this->vehicle;
    }

    public function getStops() {
        return $this->stops;
    }

    public function getLocationX() {
        return $this->locationX;
    }

    public function getLocationY() {
        return $this->locationY;
    }

}

class Stop {
    private $station;
    private $delay;
    private $time;
    function __construct($station, $delay, $time) {
        $this->station = $station;
        $this->delay = $delay;
        $this->time = $time;
    }
    public function getStation() {
        return $this->station;
    }

    public function getDelay() {
        return $this->delay;
    }

    public function getTime() {
        return $this->time;
    }

}
?>
