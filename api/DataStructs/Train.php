<?php

/**
 * Description of BTrain
 *
 * @author pieterc
 */

include_once("Vehicle.php");
class Train extends Vehicle {
    private $internalId;
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

    
}
?>
