<?php
/**
 * A liveboard can be an arrival board or departure board of a certain station
 *
 * @author pieterc
 */
class Liveboard {
    private $station;
    private $deparr;
    // these are TripNodes → arrivals or departs depending on deparr var
    private $nodes = array();

    function __construct($station, $deparr, $nodes) {
        $this->station = $station;
        $this->deparr = $deparr;
        $this->nodes = $nodes;
    }

    public function getStation() {
        return $this->station;
    }

    public function getDeparr() {
        return $this->deparr;
    }

    public function getNodes() {
        return $this->nodes;
    }
}
?>
