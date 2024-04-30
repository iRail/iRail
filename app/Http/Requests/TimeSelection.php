<?php

namespace Irail\Http\Requests;

enum TimeSelection: string
{
    case DEPARTURE = 'departure';
    case ARRIVAL = 'arrival';
}
