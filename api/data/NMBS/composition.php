<?php
/**
 * © 2019 by Open Knowledge Belgium vzw/asbl
 * This will return information about the composition of an NMBS/SNCB train.
 *
 * fillDataRoot will fill the entire dataroot with data.
 */
include_once 'data/NMBS/tools.php';
include_once 'data/NMBS/stations.php';
include_once '../includes/getUA.php';

class composition
{
    public static function fillDataRoot($dataroot, $request)
    {
        $dataroot->composition = self::scrapeComposition($request->getId(), $request->getLang());
    }

    /**
     * Scrape the composition of a train from the NMBS trainmap web application.
     * @param string $vehicleId The id of the vehicle for which the train composition should be retrieved (Example: IC587, or 587).
     * @param string $language string The request language.
     * @return TrainCompositionResult The iRail response data.
     */
    private static function scrapeComposition(string $vehicleId, string $language): TrainCompositionResult
    {
        $vehicleId = preg_replace("/S1[0-9] /", "", $vehicleId); // S10 3381 should become 3381
        $vehicleId = preg_replace("/S[0-9]/", "", $vehicleId); // S5 3381 or S53381 should become 3381
        $vehicleId = preg_replace("/[^0-9]/", "", $vehicleId);
        $data = self::getNmbsData($vehicleId, $language);

        $result = new TrainCompositionResult;
        foreach ($data as $travelsegmentWithCompositionData) {
            $result->segment[] = self::parseOneSegmentWithCompositionData($travelsegmentWithCompositionData, $language);
        }

        return $result;
    }

    private static function parseOneSegmentWithCompositionData($travelsegmentWithCompositionData, $language): TrainCompositionInSegment
    {
        $result = new TrainCompositionInSegment;
        $result->origin = stations::getStationFromID('00' . $travelsegmentWithCompositionData->ptCarFrom->uicCode, $language);
        $result->destination = stations::getStationFromID('00' . $travelsegmentWithCompositionData->ptCarTo->uicCode, $language);
        $result->composition = self::parseCompositionData($travelsegmentWithCompositionData);
        return $result;
    }

    private static function parseCompositionData($travelsegmentWithCompositionData): TrainComposition
    {
        $result = new TrainComposition();
        $result->source = $travelsegmentWithCompositionData->confirmedBy;
        $result->unit = [];
        foreach ($travelsegmentWithCompositionData->materialUnits as $compositionUnit) {
            $result->unit[] = self::parseCompositionUnit($compositionUnit);
        }
        return $result;
    }

    private static function parseCompositionUnit($compositionUnit)
    {
        return $compositionUnit; // TODO: ensure this always follows the same model
    }

    /**
     * @param string $vehicleId The vehicle ID, numeric only. IC1234 should be passed as '1234'.
     * @param string $language The request language.
     * @return array Associative array containing the response data.
     */
    private static function getNmbsData(string $vehicleId, string $language): array
    {

        include __DIR__ . '/../../../includes/getUA.php';
        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => '30',
            'useragent' => $irailAgent,
        ];

        $ch = curl_init();
        $url = "https://trainmapjs.azureedge.net/data/composition/" . $vehicleId;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }
}
