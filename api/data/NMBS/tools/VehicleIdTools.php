<?php


class VehicleIdTools
{
    /**
     * Extract the train number from a vehicle id. For example, S104522 will return 4522.
     * @param string $vehicleId
     * @return string
     */
    public static function extractTrainNumber(string $vehicleId): string
    {
        $vehicleId = strtoupper($vehicleId);
        // Handle S trains. For example, S5 3381 or S53381 should become 3381. Typically a number has 4 digits.
        $vehicleId = preg_replace("/S[12]0 ?(\d{4})/", "$1", $vehicleId); // S10, S20
        $vehicleId = preg_replace("/S3[234] ?(\d{4})/", "$1", $vehicleId); // S32, S33, S34
        $vehicleId = preg_replace("/S4[1234] ?(\d{4})/", "$1", $vehicleId); // S41, 42, 43, 44
        $vehicleId = preg_replace("/S51 ?(7\d{2})/", "$1", $vehicleId); // S51 750, 751, ...
        $vehicleId = preg_replace("/S52 ?(\d{4})/", "$1", $vehicleId); // S51, 52, 53 (those often have 3-digit numbers)
        $vehicleId = preg_replace("/S53 ?(6\d{2})/", "$1", $vehicleId); // S53 650, 651, ...
        $vehicleId = preg_replace("/S6[1234] ?(\d{4})/", "$1", $vehicleId); // S61, 62, 63, 64
        $vehicleId = preg_replace("/S81 ?([78]\d{4})/", "$1", $vehicleId); // S81 7xxx or 8xxx
        $vehicleId = preg_replace("/S[0-9] ?/", "", $vehicleId); // S1-S9
        $vehicleId = preg_replace("/[^0-9]/", "", $vehicleId);
        return $vehicleId;
    }

    public static function extractTrainType(string $vehicleId): string
    {
        return trim(substr($vehicleId, 0, -strlen(self::extractTrainNumber($vehicleId))));
    }
}
