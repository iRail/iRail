<?php
/**
 * Description of StationsRequest
 *
 * @author pieterc
 */
include_once("Request.php");
class StationsRequest implements Request{
    private $lang;
    private $country;
    function __construct($lang = "EN") {
        $this->lang = $lang;
        $expl = explode(".", $_SERVER["SERVER_NAME"]);
        $this -> country = $expl[1];
    }

    public function getLang() {
        return $this->lang;
    }

    public function getCountry() {
        return $this->country;
    }



}
?>
