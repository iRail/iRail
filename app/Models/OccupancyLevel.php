<?php

namespace Irail\Models;

use Illuminate\Support\Facades\Log;

enum OccupancyLevel: string
{
    /**
     * No data available
     *
     * GTFS: NO_DATA_AVAILABLE
     */
    case UNKNOWN = 'http://api.irail.be/terms/unknown';

    /**
     * Low occupancy.
     *
     * NMBS/SNCB: Low
     * GTFS: MANY_SEATS_AVAILABLE
     */
    case LOW = 'http://api.irail.be/terms/low';

    /**
     * Medium occupancy.
     *
     * NMBS/SNCB: Medium (yellow)
     * GTFS: FEW_SEATS_AVAILABLE
     */
    case MEDIUM = 'http://api.irail.be/terms/medium';

    /**
     * High occupancy.
     *
     * NMBS/SNCB: Medium (Orange)
     * GTFS: STANDING_ROOM_ONLY
     */
    case HIGH = 'http://api.irail.be/terms/high';

    public static function fromNmbsLevel(int $level): OccupancyLevel
    {
        switch ($level) {
            case 1:
                return OccupancyLevel::LOW;
            case 2:
                return OccupancyLevel::MEDIUM;
            case 3:
                return OccupancyLevel::HIGH;
            default:
                Log::error("Unknown NMBS occupancy level $level");
                return OccupancyLevel::UNKNOWN;
        }
    }

    public function getIntValue(): int
    {
        switch ($this) {
            default:
            case OccupancyLevel::UNKNOWN:
                throw new \InvalidArgumentException();
            case OccupancyLevel::LOW:
                return 1;
            case OccupancyLevel::MEDIUM:
                return 2;
            case OccupancyLevel::HIGH:
                return 3;
        }
    }

    public static function fromIntValue(int $value): OccupancyLevel
    {
        switch ($value) {
            default:
                throw new \InvalidArgumentException();
            case 1;
                return OccupancyLevel::LOW;
            case 2:
                return OccupancyLevel::MEDIUM;
            case 3:
                return OccupancyLevel::HIGH;
        }
    }
}
