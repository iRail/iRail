<?php

/**
 * Description of BTrain
 *
 * @author pieterc
 */

include_once("Vehicle.php");
class Train extends Vehicle {
    private $internalId;
    private $x;
    private $y;
    private $traject;
    private $countrycode;
    private $company;

    function __construct($internalId, $countrycode, $company, $x = 0, $y = 0) {
        $this->internalId = $internalId;
        $this->x = $x;
        $this->y = $y;
        $this->company = $company;
        $this->countrycode = $countrycode;
    }

    function getId(){
        return $this -> countrycode . ".". $this-> company .".".$this ->internalId;
    }

    function getInternalId(){
        return $this -> internalId;
    }
    
    function hasCoordinates(){
        return $this->x != 0 && $this->y != 0;
    }
    
    function getCoordinates(){
        return $this->y . " " . $this->x;
    }

    function hasTraject(){
        return $this->traject != null;
    }

    function getTraject(){
        return $this->traject;
    }
    
}
?>
