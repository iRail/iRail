<?php
/**
 * Description of VehicleRequest
 *
 * @author pieterc
 */
include("Vehicle.php");
include("Request.php");
class VehicleRequest implements Request {
    private $lang;
    private $vehicleId;
    function __construct($vehicleId, $lang = "EN") {
        $this-> lang = $lang;
        $this-> vehicleId = $vehicleId;
        
    }

    public function getLang() {
        return $this->lang;
    }

    public function getVehicleId() {
        return $this->vehicleId;
    }

}
?>
