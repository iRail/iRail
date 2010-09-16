<?php

/**
 * Description of Vehicle
 *
 * @author pieterc
 */
class Vehicle {
    private $id;

    function __construct($id) {
        $this->id = $id;
    }

    //hard coded for now
    function getTypeOfTransport(){
        return "trains";
    }
    
    function getId(){
        return $this->id;
    }

}
?>
