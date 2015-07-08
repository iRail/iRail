<?php
/* Copyright (C) 2011 by iRail vzw/asbl */
include_once("Printer.php");

/**
 * Prints the Kml style output. This works only for stations!!!
 *
 * Todo: change in_array to isset key lookups. This should make the whole faster
 *
 * @package output
 */
class Kml extends Printer
{
    private $ATTRIBUTES = ["id", "locationX", "locationY", "standardname", "left", "delay", "normal"];
    private $rootname;

    /**
     * printHeader()
     */
    function printHeader()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/vnd.google-earth.kml+xml");
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

    }

    /**
     * printError()
     *
     * @param $ec
     * @param $msg
     */
    function printError($ec, $msg)
    {
        $this->printHeader();
        header("HTTP/1.1 $ec $msg");
        echo "<error code=\"$ec\">$msg</error>";
    }

    /**
     * startRootElement
     *
     * @param $name
     * @param $version
     * @param $timestamp
     * @return mixed|void
     */
    function startRootElement($name, $version, $timestamp)
    {
        $this->rootname = $name;
        if ($name == "stations") {
            echo "<kml xmlns=\"http://www.opengis.net/kml/2.2\">";
        } else {
            $this->printError(400, "KML only works for stations at this moment");
        }
    }
//make a stack of array information, always work on the last one
//for nested array support
    private $stack = [];
    private $arrayindices = [];
    private $currentarrayindex = -1;


    /**
     * startArray()
     *
     * @param $name
     * @param $number
     * @param bool $root
     */
    function startArray($name, $number, $root = false)
    {
    }

    /**
     * nextArrayElement()
     */
    function nextArrayElement()
    {
        $this->arrayindices[$this->currentarrayindex]++;
    }

    /**
     * startObject()
     *
     * @param $name
     * @param $object
     */
    function startObject($name, $object)
    {
        if ($name == "station") {
            echo "<Placemark id='" . $object->id . "'><name>" . $object->name . "</name><Point><coordinates>" . $object->locationX . "," . $object->locationY . "</coordinates></Point></Placemark>";
        }
    }

    /**
     * startKeyVal
     *
     * @param $key
     * @param $val
     */
    function startKeyVal($key, $val)
    {
    }

    /**
     * endElement()
     *
     * @param $name
     */
    function endElement($name)
    {
    }

    /**
     * endArray()
     *
     * @param $name
     * @param bool $root
     */
    function endArray($name, $root = false)
    {
    }

    /**
     * endRootElement
     *
     * @param $name
     */
    function endRootElement($name)
    {
        echo "</kml>";
    }
}

;
