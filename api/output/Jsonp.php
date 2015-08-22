<?php
namespace Irail\Api\output;


/* Copyright (C) 2011 by iRail vzw/asbl */

/**
 * Prints the Jsonp style output
 *
 * @package output
 */
class Jsonp extends \Irail\Api\output\Json
{
    public function printHeader()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/javascript;charset=UTF-8");
    }

    public function printBody()
    {
        $callback = $_GET['callback'];
        echo "$callback(";
        parent::printBody($this->documentRoot);
        echo ")";
    }

    /**
     * @param $ec
     * @param $msg
     * @return mixed|void
     */
    public function printError($ec, $msg)
    {
        $this->printHeader();
        header("HTTP/1.1 $ec $msg");
        echo $_GET['callback'] . "({\"error\":$ec, \"message\": \"$msg\"})";
    }

};
