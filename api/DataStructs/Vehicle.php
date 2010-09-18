<?php

/**
 * Description of Vehicle
 *
 * @author pieterc
 */
abstract class Vehicle {
    
    abstract function getId();

    abstract function hasCoordinates();
    abstract function getCoordinates();

    abstract function hasTraject();
    abstract function getTraject();
}
?>
