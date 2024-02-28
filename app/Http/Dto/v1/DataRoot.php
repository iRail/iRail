<?php

namespace Irail\Http\Dto\v1;

/* Copyright (C) 2011 by iRail vzw/asbl */

use AllowDynamicProperties;
use Exception;

/**
 * This is the root of every document. It will specify a version and timestamp. It also has the printer class to print the entire document.
 */
#[AllowDynamicProperties]
class DataRoot
{
    private $rootName;

    public $version;
    public $timestamp;

    /**
     * constructor of this class.
     *
     * @param        $rootname
     * @throws Exception
     * @internal param format $string the format of the document: json, json or XML
     */
    public function __construct($rootName)
    {
        $this->rootName = $rootName;
        $this->version = '1.3';
        $this->timestamp = date('U');
    }

    public function getRootName():string{
        return $this->rootName;
    }
}
