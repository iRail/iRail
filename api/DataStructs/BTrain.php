<?php

/**
 * Description of BTrain
 *
 * @author pieterc
 */

include("Vehicle.php");
class BTrain extends Vehicle {
    private $internalId;
    private $x;
    private $y;
    private $traject;

    function __construct($internalId, $x = 0, $y = 0) {
        $this->internalId = $internalId;
        $this->x = $x;
        $this->y = $y;
    }

    function getId(){
        return "Be."."NMBS.".$this ->internalId;
    }
    
    function hasCoordinates(){
        return $x != 0 && $y != 0;
    }
    
    function getCoordinates(){
        return $y . " " . $x;
    }

    function hasTraject(){
        return $traject != null;
    }

    function getTraject(){
        return $traject;
    }
    
}
?>
