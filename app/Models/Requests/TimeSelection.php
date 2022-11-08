<?php

namespace Irail\Models\Requests;

enum TimeSelection: string
{
    case DEPARTURE = 'departure';
    case ARRIVAL = 'arrival';
}
