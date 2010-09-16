<?php
/**
 * Description of TripNode
 *
 * @author pieterc
 */
class TripNode {
    private $platform;
    private $delay;
    private $unixtime;
    private $date;
    private $station;
    private $vehicle;

    function __construct($platform, $delay, $unixtime, $station, $vehicle) {
        $this->platform = $platform;
        $this->delay = $delay;
        $this->unixtime = $unixtime;
        $this->station = $station;
        $this->vehicle = $vehicle;
    }

    public function getPlatform() {
        return $this->platform;
    }

    public function getDelay() {
        return $this->delay;
    }

    public function getTime() {
        return $this->unixtime;
    }

    public function getStation() {
        return $this->station;
    }

    public function getVehicle() {
        return $this->vehicle;
    }



}
?>
