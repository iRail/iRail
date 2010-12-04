<?php
/**
 * This is the Connection class. It contains data.
 *
 * @author pieterc
 */
class Connection {
    private $depart; //in unixtime...
    private $arrival;
    private $vias = array();
    private $duration;
    
    function __construct($depart, $arrival, $vias, $duration) {
        $this->depart = $depart;
        $this->arrival = $arrival;
        $this->vias = $vias;
        $this->duration = $duration;
    }

    public function getDepart() {
        return $this->depart;
    }

    public function getArrival() {
        return $this->arrival;
    }

    public function getVias() {
        return $this->vias;
    }

    public function getDuration() {
        return $this->duration;
    }
}
?>
