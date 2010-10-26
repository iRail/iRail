<?php
/**
 * Description of StationsRequest
 *
 * @author pieterc
 */
ini_set("include_path", ".:../:api/DataStructs:DataStructs:../includes:includes");

include_once("Request.php");
include_once("InputHandlers/NSStationsInput.php");
include_once("InputHandlers/BRailStationsInput.php");
class StationsRequest extends Request{
    private $lang;
    function __construct($lang = "EN") {
        $this->lang = $lang;

    }

    public function getLang() {
        return $this->lang;
    }

        /**
     * This function serves as a factory method
     * It provides something with an input
     * @return Input
     */
    public function getInput(){
        if(parent::getCountry() == "nl"){
            return new NSStationsInput();
        }else if(parent::getCountry()=="be"){
            return new BRailStationsInput();
        }else{
            return new NSStationsInput();
        }
    }



}
?>
