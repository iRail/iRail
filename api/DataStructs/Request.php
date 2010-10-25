<?php

/**
 * This is an interface to a Request
 *
 * @author pieterc
 */
abstract class Request {
    protected $country;
    
    public function getCountry() {
        $expl = explode(".", $_SERVER["SERVER_NAME"]);
        $this -> country = $expl[sizeof($expl)-1];
        return $this->country;
    }


}
?>
