<?php

namespace Irail\Models;

enum DepartureArrivalState: string
{
    case APPROACHING = 'APPROACHING';
    case HALTING = 'HALTING';
    case LEFT = 'LEFT';

    public function hasArrived(): bool
    {
        return $this == DepartureArrivalState::HALTING || $this == DepartureArrivalState::LEFT;
    }

    public function hasLeft(): bool
    {
        return $this == DepartureArrivalState::LEFT;
    }
}
