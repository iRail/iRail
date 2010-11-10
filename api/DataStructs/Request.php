<?php

/**
 * This is an interface to a Request
 *
 * @author pieterc
 */
abstract class Request {
    private $format = "xml";
    
    function __construct($format) {
        $this->format = $format;
    }

    /**
     *
     * @return countrycode
     */
    protected function getCountry() {
        $expl = explode(".", $_SERVER["SERVER_NAME"]);
        return $expl[sizeof($expl)-1];
    }

    /**
     * This function serves as a factory method design pattern.
     */
    public abstract function getInput();
    public abstract function getOutput($datastruct);
    
    public function getFormat() {
        return strtolower($this->format);
    }
    
}
?>
