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
include_once("OutputHandlers/JSONVehicleOutput.php");
include_once("OutputHandlers/XMLVehicleOutput.php");
class VehicleRequest extends Request {
    private $vehicleId;
    function __construct($vehicleId, $lang = "EN", $format = "xml") {
        parent::__construct($format, $lang);
        $this-> vehicleId = $vehicleId;
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

    public function getOutput($vehicle){
        if(parent::getFormat() == "xml"){
            return new XMLVehicleOutput(parent::getLang() ,$vehicle);
        }else if(parent::getFormat() == "json"){
            return new JSONVehicleOutput(parent::getLang() ,$vehicle);
        }else{
            throw new Exception("No outputformat specified");
        }
    }
}
?>
