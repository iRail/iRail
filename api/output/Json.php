<?php

/* Copyright (C) 2011 by iRail vzw/asbl */
/**
 * Prints the Json style output.
 */
include_once 'Printer.php';

class Json extends Printer
{
    private $rootname;

    // Make a stack of array information, always work on the last one
    // for nested array support
    private $stack = [];
    private $arrayindices = [];
    private $currentarrayindex = -1;

    public function printHeader()
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json;charset=UTF-8');
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
        echo "{\"error\":$ec,\"message\":\"$msg\"}";
    }

    /**
     * @param $name
     * @param $version
     * @param $timestamp
     * @return mixed|void
     */
    public function startRootElement($name, $version, $timestamp)
    {
        $this->rootname = $name;
        echo "{\"version\":\"$version\",\"timestamp\":\"$timestamp\",";
    }

    /**
     * @param $name
     * @param $number
     * @param bool $root
     * @return mixed|void
     */
    public function startArray($name, $number, $root = false)
    {
        if (! $root || $this->rootname == 'liveboard' || $this->rootname == 'vehicleinformation') {
            echo '"'.$name."s\":{\"number\":\"$number\",";
        }

        echo "\"$name\":[";

        $this->currentarrayindex++;
        $this->stack[$this->currentarrayindex] = $name;
        $this->arrayindices[$this->currentarrayindex] = 0;
    }

    public function nextArrayElement()
    {
        echo ',';
        $this->arrayindices[$this->currentarrayindex]++;
    }

    public function nextObjectElement()
    {
        echo ',';
    }

    /**
     * @param $name
     * @param $object
     * @return mixed|void
     */
    public function startObject($name, $object)
    {
        if ($this->currentarrayindex > -1 && $this->stack[$this->currentarrayindex] == $name) {
            echo '{';
            // Show id (in array) except if array of stations (compatibility issues)
            if ($name != 'station') {
                echo '"id":"'.$this->arrayindices[$this->currentarrayindex].'",';
            }
        } else {
            if ($this->rootname != 'stations' && $name == 'station' || $name == 'platform') {
                // split station and platform into station/platform and stationinfo/platforminfox,
                // to be compatible with 1.0
                echo "\"$name\":\"$object->name\",";
                echo '"'.$name.'info":{';
            } elseif ($this->rootname != 'vehicle' && $name == 'vehicle') {
                // split vehicle into vehicle and vehicleinfo to be compatible with 1.0
                echo "\"$name\":\"$object->name\",";
                echo '"'.$name.'info":{';
            } else {
                echo "\"$name\":{";
            }
        }
    }

    /**
     * @param $key
     * @param $val
     * @return mixed|void
     */
    public function startKeyVal($key, $val)
    {
        echo "\"$key\":\"$val\"";
    }

    /**
     * @param $name
     * @param bool $root
     * @return mixed|void
     */
    public function endArray($name, $root = false)
    {
        $this->stack[$this->currentarrayindex] = '';
        $this->arrayindices[$this->currentarrayindex] = 0;
        $this->currentarrayindex--;

        if ($root && $this->rootname != 'liveboard' && $this->rootname != 'vehicleinformation') {
            echo ']';
        } else {
            echo ']}';
        }
    }

    /**
     * @param $name
     */
    public function endObject($name)
    {
        echo '}';
    }

    /**
     * @param $name
     * @return mixed|void
     */
    public function endElement($name)
    {
    }

    /**
     * @param $name
     * @return mixed|void
     */
    public function endRootElement($name)
    {
        echo '}';
    }
};
