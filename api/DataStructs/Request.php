<?php

/**
 * This is an interface to a Request
 *
 * @author pieterc
 */
abstract class Request {

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


}
?>
