<?php

/* Copyright (C) 2011 by iRail vzw/asbl */

/**
 * Prints the Json style output.
 */

namespace Irail\Legacy\Output;

class Json extends Printer
{
    private $rootname;

    // Make a stack of array information, always work on the last one
    // for nested array support
    private $stack = [];
    private $arrayindices = [];
    private $currentarrayindex = -1;

    public function getHeaders(): array
    {
        return ['Access-Control-Allow-Origin' => '*', 'Content-Type' => 'application/json;charset=UTF-8'];
    }

    /**
     * @param $ec
     * @param $msg
     * @return mixed|void
     */
    public function getError($ec, $msg): string
    {
        return "{\"error\":$ec,\"message\":\"$msg\"}";
    }

    /**
     * @param $name
     * @param $version
     * @param $timestamp
     * @return mixed|void
     */
    public function startRootElement($name, $version, $timestamp): string
    {
        $this->rootname = $name;
        return "{\"version\":\"$version\",\"timestamp\":\"$timestamp\",";
    }

    /**
     * @param      $name
     * @param      $number
     * @param bool $root
     * @return mixed|void
     */
    public function startArray($name, $number, $root = false): string
    {
        $result = "";
        if (!$root || $this->rootname == 'liveboard' || $this->rootname == 'vehicleinformation') {
            $result .= '"' . $name . "s\":{\"number\":\"$number\",";
        }

        $result .= "\"$name\":[";

        $this->currentarrayindex++;
        $this->stack[$this->currentarrayindex] = $name;
        $this->arrayindices[$this->currentarrayindex] = 0;
    }

    public function nextArrayElement(): string
    {
        return ',';
        $this->arrayindices[$this->currentarrayindex]++;
    }

    public function nextObjectElement(): string
    {
        return ',';
    }

    /**
     * @param $name
     * @param $object
     * @return mixed|void
     */
    public function startObject($name, $object): string
    {
        $result = "";
        if ($this->currentarrayindex > -1 && $this->stack[$this->currentarrayindex] == $name) {
            $result .= '{';
            // Show id (in array) except if array of stations (compatibility issues)
            if ($name != 'station') {
                $result .= '"id":"' . $this->arrayindices[$this->currentarrayindex] . '",';
            }
        } else {
            if ($this->rootname != 'StationsDatasource' && $name == 'station' || $name == 'platform') {
                // split station and platform into station/platform and stationinfo/platforminfox,
                // to be compatible with 1.0
                $result .= "\"$name\":\"$object->name\",";
                $result .= '"' . $name . 'info":{';
            } else if ($this->rootname != 'vehicle' && $name == 'vehicle') {
                // split vehicle into vehicle and vehicleinfo to be compatible with 1.0
                $result .= "\"$name\":\"$object->name\",";
                $result .= '"' . $name . 'info":{';
            } else {
                $result .= "\"$name\":{";
            }
        }
        return $result;
    }

    /**
     * @param $key
     * @param $val
     * @return mixed|void
     */
    public function startKeyVal($key, $val): string
    {
        $val = trim(json_encode($val), '"');
        return "\"$key\":\"$val\"";
    }

    /**
     * @param      $name
     * @param bool $root
     * @return mixed|void
     */
    public function endArray($name, $root = false): string
    {
        $this->stack[$this->currentarrayindex] = '';
        $this->arrayindices[$this->currentarrayindex] = 0;
        $this->currentarrayindex--;

        if ($root && $this->rootname != 'liveboard' && $this->rootname != 'vehicleinformation') {
            return ']';
        } else {
            return ']}';
        }
    }

    /**
     * @param $name
     */
    public function endObject($name): string
    {
        return '}';
    }

    /**
     * @param $name
     * @return mixed|void
     */
    public function endElement($name): string
    {
        return "";
    }

    /**
     * @param $name
     * @return mixed|void
     */
    public function endRootElement($name): string
    {
        return '}';
    }
}
