<?php

/**
 * This is an interface to a Request
 *
 * @author pieterc
 */
class Request {
    private $SUPPORTED_LANGUAGES = array("EN", "NL", "FR");

    private $format = "xml";
    private $lang = "EN";
    function __construct($format, $lang) {
        $this->format = $format;
        $this-> lang = strtoupper($lang);
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
        }else if(strtoupper($country) == "COM"){
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
        if(in_array($this->lang, $this->SUPPORTED_LANGUAGES)){
            return $this->lang;
        }
        return "EN";
    }
    
}
?>
