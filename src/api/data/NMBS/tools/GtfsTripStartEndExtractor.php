<?php

namespace Irail\api\data\NMBS\tools;

use Carbon\Carbon;
use Exception;
use Irail\api\data\models\hafas\VehicleWithOriginAndDestination;

class GtfsTripStartEndExtractor
{

    /**
     * @param string $vehicleId The vehicle name/id, such as IC538
     * @param string $date The date in YYYYmmdd format
     * @return false|VehicleWithOriginAndDestination
     * @throws Exception
     */
    public static function getVehicleWithOriginAndDestination(string $vehicleId, string $date): VehicleWithOriginAndDestination|false
    {
        $vehicleNumber = (int)filter_var($vehicleId, FILTER_SANITIZE_NUMBER_INT);
        $vehicleDetailsForDate = self::getTripsWithStartAndEndByDate($date);
        foreach ($vehicleDetailsForDate as $vehicleWithOriginAndDestination) {
            if ($vehicleWithOriginAndDestination->getVehicleNumber() == $vehicleNumber) {
                return $vehicleWithOriginAndDestination;
            }
        }
        return false;
    }

    /**
     * @return VehicleWithOriginAndDestination[]
     * @throws Exception
     */
    public static function getTripsWithStartAndEndByDate(string $date): array
    {
        $vehicleDetailsByDate = Tools::getCachedObject("gtfs|vehicleDetailsByDate");
        if ($vehicleDetailsByDate === false) {
            $serviceIdsByCalendarDate = self::readCalendarDates();
            // Only keep service ids in a specific date range (x days back, y days forward), to keep cpu/ram usage down
            $serviceIdsToRetain = self::getServiceIdsInDateRange($serviceIdsByCalendarDate, $date, -3, +14);
            $vehicleDetailsByServiceId = self::readTrips($serviceIdsToRetain);

            if (empty($serviceIdsByCalendarDate) || empty($vehicleDetailsByServiceId)) {
                throw new Exception("No response from iRail GTFS", 504);
            }

            // Create a multi-dimensional array:
            // date (yyyymmdd) => VehicleWithRoute[]
            $vehicleDetailsByDate = [];
            foreach ($serviceIdsByCalendarDate as $date => $serviceIds) {
                $vehicleDetailsByDate[$date] = [];
                foreach ($serviceIds as $serviceId) {
                    if (key_exists($serviceId, $vehicleDetailsByServiceId)) {
                        foreach ($vehicleDetailsByServiceId[$serviceId] as $vehicleDetails) {
                            $vehicleDetailsByDate[$date][] = $vehicleDetails;
                        }
                    }
                }
            }

            Tools::setCachedObject("gtfs|vehicleDetailsByDate", $vehicleDetailsByDate, 7200);
        }
        return $vehicleDetailsByDate[$date];
    }

    private static function readCalendarDates()
    {
        $fileStream = fopen("https://gtfs.irail.be/nmbs/gtfs/latest/calendar_dates.txt", "r");
        $serviceIdsByDate = [];

        $SERVICE_ID_COLUMN = 0;
        $DATE_COLUMN = 1;

        $headers = fgetcsv($fileStream); // ignore the headers
        while ($row = fgetcsv($fileStream)) {
            if (!key_exists($row[$DATE_COLUMN], $serviceIdsByDate)) {
                $serviceIdsByDate[$row[$DATE_COLUMN]] = [];
            }
            $serviceIdsByDate[$row[$DATE_COLUMN]][] = intval($row[$SERVICE_ID_COLUMN]);
        }
        return $serviceIdsByDate;
    }

    /**
     * @return VehicleWithOriginAndDestination[]
     */
    private static function readTrips(array $serviceIdsToRetain): array
    {
        $fileStream = fopen("https://gtfs.irail.be/nmbs/gtfs/latest/trips.txt", "r");
        $vehicleDetailsByServiceId = [];

        $SERVICE_ID_COLUMN = 1;
        $TRIP_ID_COLUMN = 2;
        $TRIP_SHORT_NAME_COLUMN = 4;

        $headers = fgetcsv($fileStream); // ignore the headers
        while ($row = fgetcsv($fileStream)) {
            $serviceId = intval($row[$SERVICE_ID_COLUMN]);
            $tripId = $row[$TRIP_ID_COLUMN];

            if (!in_array($serviceId, $serviceIdsToRetain)) {
                continue;
            }

            if (!key_exists($serviceId, $vehicleDetailsByServiceId)) {
                $vehicleDetailsByServiceId[$serviceId] = [];
            }

            $trainNumber = intval($row[$TRIP_SHORT_NAME_COLUMN]);
            $tripIdParts = explode(':', $tripId);
            $vehicleDetails = new VehicleWithOriginAndDestination($trainNumber, $tripIdParts[3], $tripIdParts[4]);
            $vehicleDetailsByServiceId[$serviceId][] = $vehicleDetails;
        }

        return $vehicleDetailsByServiceId;
    }

    private static function getServiceIdsInDateRange(array $serviceIdsByCalendarDate, string $date,
        int $daysBack, int $daysForward)
    {
        $date = Carbon::createFromFormat('Ymd', $date);
        $serviceIdsToKeep = [];

        $startDate = $date->copy()->subDays(abs($daysBack));
        $endDate = $date->copy()->addDays(abs($daysForward));
        for ($dateToKeep = $startDate; $dateToKeep <= $endDate; $dateToKeep->addDay()) {
            print ("Keep " . $dateToKeep->format('Ymd'));
            $serviceIdsOnDay = $serviceIdsByCalendarDate[$dateToKeep->format('Ymd')];
            foreach ($serviceIdsOnDay as $serviceIdOnDay) {
                $serviceIdsToKeep[] = $serviceIdOnDay;
            }
        }
        return array_unique($serviceIdsToKeep);
    }
}