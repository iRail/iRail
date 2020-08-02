<?php

namespace Irail\api\data\models;

/** Copyright (C) 2011 by iRail vzw/asbl
 * This file contains classes used in API responses.
 *
 * @author pieterc
 */
class Disturbance
{
    public $title;
    public $description;
    // public $attachment; // Not compulsory, commented to ensure null values don't cause issues in the printer
    public $link; // Not compulsory
    public $type;
    public $timestamp;
}
