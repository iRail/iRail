<?php

namespace Irail\Models;

enum DepartureArrivalState: string
{
    case INCOMING = 'INCOMING';
    case HALTING = 'HALTING';
    case LEFT = 'LEFT';
}
