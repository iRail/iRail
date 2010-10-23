<?php
/**
 * Description of StationsRequest
 *
 * @author pieterc
 */
include_once("Request.php");
class StationsRequest implements Request{
    private $lang;
    function __construct($lang = "EN") {
        $this->lang = $lang;
    }

    public function getLang() {
        return $this->lang;
    }

}
?>
