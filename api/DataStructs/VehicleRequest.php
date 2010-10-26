<?php
/**
 * Description of VehicleRequest
 *
 * @author pieterc
 */
ini_set("include_path", ".:../:api/DataStructs:DataStructs:../includes:includes");

include_once("Vehicle.php");
include_once("Request.php");
include_once("InputHandlers/NSVehicleInput.php");
include_once("InputHandlers/BRailVehicleInput.php");
class VehicleRequest extends Request {
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

    /**
     * This function serves as a factory method
     * It provides something with an input
     * @return Input
     */
    public function getInput(){
        if(parent::getCountry() == "nl"){
            return new NSVehicleInput();
        }else if(parent::getCountry()=="be"){
            return new BRailVehicleInput();
        }else{
            return new NSVehicleInput();
        }
    }

}
?>
