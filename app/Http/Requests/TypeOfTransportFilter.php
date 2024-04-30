<?php

namespace Irail\Http\Requests;

enum TypeOfTransportFilter
{
    case ALL;
    case TRAINS;
    case NO_INTERNATIONAL_TRAINS;
    case AUTOMATIC;
}
