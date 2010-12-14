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
    private $station;
    private $vehicle;
    private $platformNormal; // This is a boolean to indicate whether the platform has changed recently
    private $left;

    function __construct($platform, $delay, $unixtime, $station, $vehicle, $platformNormal = true, $left = false) {
        $this->platform = $platform;
        $this->delay = $delay;
        $this->unixtime = $unixtime;
        $this->station = $station;
        $this->vehicle = $vehicle;
        $this->platformNormal = $platformNormal;
	$this->left = $left;
    }

    public function getLeft(){
	 return $this->left;
    }
    

    public function normalPlatform(){
        return $this->platformNormal;
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
