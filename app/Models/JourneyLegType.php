<?php

namespace Irail\Models;

enum JourneyLegType: string
{
    case WALKING = 'WALK';
    case JOURNEY = 'TRANSPORT';
    case CHECK_IN = "CHKI";
}
