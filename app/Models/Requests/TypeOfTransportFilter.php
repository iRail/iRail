<?php

namespace Irail\Models\Requests;

enum TypeOfTransportFilter
{
    case ALL_TRAINS;
    case NO_INTERNATIONAL_TRAINS;
    case AUTOMATIC;
}