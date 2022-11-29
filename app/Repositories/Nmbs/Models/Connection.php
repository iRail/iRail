<?php

namespace Irail\Repositories\Nmbs\Models;

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
