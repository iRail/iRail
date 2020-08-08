<?php

namespace Irail\api\data;

/* Copyright (C) 2011 by iRail vzw/asbl */

use Irail\api\data\NMBS\Composition;
use Irail\api\data\NMBS\Connections;
use Irail\api\data\NMBS\Disturbances;
use Irail\api\data\NMBS\Liveboard;
use Irail\api\data\NMBS\VehicleInformation;
use Irail\api\output\Printer;
use Irail\api\requests\CompositionRequest;
use Irail\api\requests\ConnectionsRequest;
use Irail\api\requests\DisturbancesRequest;
use Irail\api\requests\LiveboardRequest;
use Irail\api\requests\Request;
use Irail\api\requests\VehicleinformationRequest;

/**
 * This is the root of every document. It will specify a version and timestamp. It also has the printer class to print the entire document.
 */
class DataRoot
{
    private $printer;
    private $rootname;

    public $version;
    public $timestamp;

    /**
     * constructor of this class.
     *
     * @param $rootname
     * @param float $version the version of the API
     * @param $format
     * @param string $error
     * @throws \Exception
     * @internal param format $string the format of the document: json, json or XML
     */
    public function __construct($rootname, $version, $format, $error = '')
    {
        //We're making this in the class form: Json or Xml or Jsonp
        $format = ucfirst(strtolower($format));
        //fallback for when callback is set but not the format= Jsonp
        if (isset($_GET['callback']) && $format == 'Json') {
            $format = 'Jsonp';
        }

        $this->printer = Printer::getPrinterInstance($format, $this);
        $this->version = $version;
        $this->timestamp = date('U');
        $this->rootname = $rootname;
    }

    /**
     * Print everything.
     */
    public function printAll()
    {
        $this->printer->printAll();
    }

    /**
     * @return mixed
     */
    public function getRootname()
    {
        return $this->rootname;
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function fetchData($request)
    {
        try {
            if ($request instanceof LiveboardRequest) {
                Liveboard::fillDataRoot($this, $request);
            } elseif ($request instanceof ConnectionsRequest) {
                Connections::fillDataRoot($this, $request);
            } elseif ($request instanceof CompositionRequest) {
                Composition::fillDataRoot($this, $request);
            } elseif ($request instanceof VehicleinformationRequest) {
                VehicleInformation::fillDataRoot($this, $request);
            } elseif ($request instanceof DisturbancesRequest) {
                Disturbances::fillDataRoot($this, $request);
            }
        } catch (\Exception $e) {
            if ($e->getCode() == '404') {
                throw new \Exception($e->getMessage(), 404);
            } elseif ($e->getCode() == '300') {
                throw new \Exception($e->getMessage(), 300);
            } else {
                throw new \Exception(
                    'Could not get data: ' . $e->getMessage() . '. Please report this issue at https://github.com/irail/irail/issues/new',
                    $e->getCode()
                );
            }
        }
    }
}
