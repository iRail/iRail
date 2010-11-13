<?php

/**
 * This is an interface to a Request
 *
 * @author pieterc
 */
class Request {
    private $format = "xml";
    private $lang = "EN";
    function __construct($format, $lang) {
        $this->format = $format;
        $this-> lang = $lang;
    }

    /**
     *
     * @return countrycode
     */
    public function getCountry() {
        $expl = explode(".", $_SERVER["SERVER_NAME"]);
        $country = $expl[sizeof($expl)-1];
        if($country == "localhost"){ // added this line for testing locally
            return "be";
        }
        return $country;
    }

    /**
     * This function serves as a factory method design pattern.
     */
    public function getInput(){}
    public function getOutput($datastruct){}
    
    public function getFormat() {
        return strtolower($this->format);
    }

    public function getLang() {
        return $this->lang;
    }
    
}
?>
