<?php

namespace Irail\Models;

enum JourneyLegType: string
{
    case WALKING = 'WALK';
    case JOURNEY = 'JNY';
    case CHECK_IN = "CHKI";
}
