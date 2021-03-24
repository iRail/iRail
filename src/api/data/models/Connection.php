<?php

namespace Irail\api\data\models;

class Connection
{
    public $departure;

    public $arrival;

    public $via;
    // not compulsory
    public $duration;
    public $remark = [];
    public $alert = [];
}
