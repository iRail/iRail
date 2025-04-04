<?php

namespace Irail\Repositories\Nmbs\Traits;

use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Exceptions\NoResultsException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Exceptions\Upstream\UpstreamServerUnavailableException;
use Irail\Models\DepartureAndArrival;
use Irail\Models\DepartureArrivalState;
use Irail\Models\DepartureOrArrival;
use Irail\Models\Message;
use Irail\Models\MessageLink;
use Irail\Models\MessageType;
use Irail\Models\PlatformInfo;
use Irail\Models\Vehicle;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\Models\HafasVehicle;

trait BasedOnHafas
{
    /**
     * @param string $rawJsonData data to decode.
     * @return array an associative array representing the JSON response
     * @throws UpstreamServerException thrown when the response is invalid or describes an error
     * @throws NoResultsException
     */
    protected function deserializeAndVerifyResponse(string $rawJsonData): array
    {
        if (empty($rawJsonData)) {
            throw new UpstreamServerUnavailableException('The server did not return any data.');
        }

        $this->throwExceptionOnInvalidResponse($json);
        return $json;
    }




    /**
     * Check whether the status of the arrival equals cancelled.
     *
     * @param string $status The status to check.
     *
     * @return bool True if the arrival is cancelled, or if the status has an unrecognized value.
     */
    public function isArrivalCanceledBasedOnState(string $status): bool
    {
        if ($status == 'SCHEDULED' ||
            $status == 'REPORTED' ||
            $status == 'PROGNOSED' ||
            $status == 'CALCULATED' ||
            $status == 'CORRECTED' ||
            $status == 'PARTIAL_FAILURE_AT_DEP') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check whether or not the status of the departure equals cancelled.
     *
     * @param string $status The status to check.
     *
     * @return bool True if the departure is cancelled, or if the status has an unrecognized value.
     */
    public function isDepartureCanceledBasedOnState(string $status): bool
    {
        if ($status == 'SCHEDULED' ||
            $status == 'REPORTED' ||
            $status == 'PROGNOSED' ||
            $status == 'CALCULATED' ||
            $status == 'CORRECTED' ||
            $status == 'PARTIAL_FAILURE_AT_ARR') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Parse the list which contains information about all the alerts which are used in this API response.
     * Alerts warn about service interruptions etc.
     *
     * @param $json
     *
     * @return Message[]
     * @throws InternalProcessingException
     */
    public function parseAlerts($json): array
    {
        if (!key_exists('Messages', $json)) {
            return [];
        }

        $messages = [];
        foreach ($json['Messages']['Message'] as $rawAlert) {
            /*
                              {
                  "affectedStops": {
                    "StopLocation": [
                      ...
                    ]
                  },
                  "validFromStop": {
                    "name": "Gent-Sint-Pieters",
                    "id": "A=1@O=Gent-Sint-Pieters@X=3710675@Y=51035897@U=80@L=8892007@",
                    "extId": "8892007",
                    "lon": 3.710675,
                    "lat": 51.035897
                  },
                  "validToStop": {
                    "name": "Antwerpen-Centraal",
                    "id": "A=1@O=Antwerpen-Centraal@X=4421102@Y=51217200@U=80@L=8821006@",
                    "extId": "8821006",
                    "lon": 4.421102,
                    "lat": 51.2172
                  },
                  "channel": [
                    ...
                  ],
                  "id": "66738",
                  "act": true,
                  "head": "Kortrijk - Deinze",
                  "lead": "We are conducting work for you between Kortrijk and Deinze.",
                  "text": "We are conducting work for you between Kortrijk and Deinze. Detailed information only available in French (FR) and in Dutch (NL).",
                  "company": "SNCB",
                  "category": "1",
                  "priority": 50,
                  "products": 57348,
                  "modTime": "11:45:57",
                  "modDate": "2022-10-17",
                  "icon": "HIM1",
                  "routeIdxFrom": 0,
                  "routeIdxTo": 14,
                  "sTime": "03:00:00",
                  "sDate": "2022-10-29",
                  "eTime": "23:59:00",
                  "eDate": "2022-11-06"
                }
              }*/

            $id = $rawAlert['id'];
            $header = strip_tags($rawAlert['head']);
            $description = $rawAlert['text'];
            // read Lead if present, fall back to the first sentence if unavailable
            $lead = key_exists('lead', $rawAlert) ? strip_tags($rawAlert['lead']) : substr($description, 0, strpos($description, '.'));

            $startTime = $this->parseDateAndTime(
                $rawAlert['sDate'],
                $rawAlert['sTime']
            );
            $endTime = $this->parseDateAndTime(
                $rawAlert['eDate'],
                $rawAlert['eTime']
            );
            $modifiedTime = key_exists('modDate', $rawAlert) ? $this->parseDateAndTime(
                $rawAlert['modDate'],
                $rawAlert['modTime']
            ) : $startTime;
            $organisation = $rawAlert['company'];

            $links = [];
            foreach ($rawAlert['channel'] as $channel) {
                if (key_exists('url', $channel)) {
                    foreach ($channel['url'] as $url) {
                        $links[] = new MessageLink($url['name'], $url['url']);
                    }
                }
            }

            $type = MessageType::TROUBLE;
            switch ($rawAlert['category']) {
                case '1':
                    $type = MessageType::WORKS;
                    break;
                case '2': // Seen for example on 'Stiltezones'
                case '11': // Seen for example on 'Kust-expres
                    $type = MessageType::INFO;
                    break;
            }

            $messages[] = new Message($id, $startTime, $endTime, $modifiedTime, $type, $header, $lead, $description, $organisation, $links);
        }
        return $messages;
    }


    /**
     * Parse the arrival platform, and whether this is a normal platform or a changed one
     * @param array $departureOrArrival
     * @return PlatformInfo The platform for this departure.
     */
    public function parsePlatform(array $departureOrArrival): PlatformInfo
    {
        if (key_exists('depTrack', $departureOrArrival)) {
            return $this->parsePlatformFields($departureOrArrival, 'depTrack', 'rtDepTrack');
        }
        if (key_exists('arrTrack', $departureOrArrival)) {
            return $this->parsePlatformFields($departureOrArrival, 'arrTrack', 'rtArrTrack');
        }
        return $this->parsePlatformFields($departureOrArrival);
    }

    /**
     * @param array  $data The data object containing the platform information, for example a departure or arrival.
     * @param string $scheduledFieldName The name of the field containing information about the scheduled platform.
     * @param string $realTimeFieldName The name of the field containing information about the realtime platform.
     * @return PlatformInfo The platform for this departure/arrival.
     */
    private function parsePlatformFields(array $data, string $scheduledFieldName = 'track', string $realTimeFieldName = 'rtTrack'): PlatformInfo
    {
        if (key_exists($realTimeFieldName, $data)) {
            // Realtime correction exists
            return new PlatformInfo(null, $data[$realTimeFieldName], true);
        } else {
            if (key_exists($scheduledFieldName, $data)) {
                // Only scheduled data exists
                return new PlatformInfo(null, $data[$scheduledFieldName], false);
            } else {
                // No data
                return new PlatformInfo(null, '?', false);
            }
        }
    }

    /**
     * @param $product
     * @return HafasVehicle
     */
    public function parseProduct($product): HafasVehicle
    {
        return new HafasVehicle(trim($product['num']), trim($product['catOutL']), trim($product['name']));
    }

    /**
     * Parse an intermediate stop for a train on a connection. For example, if a traveller travels from
     * Brussels South to Brussels north, Brussels central would be an intermediate stop (the train stops but
     * the traveller stays on)
     * @param         $lang
     * @param         $rawIntermediateStop
     * @param Vehicle $vehicle
     * @return DepartureAndArrival The parsed intermediate stop.
     * @throws InternalProcessingException
     * @throws UnknownStopException
     */
    private function parseHafasIntermediateStop(
        StationsRepository $stationsRepository,
        array $rawIntermediateStop,
        Vehicle $vehicle,
    ): DepartureAndArrival {
        $intermediateStop = new DepartureAndArrival();
        $station = $stationsRepository->getStationByHafasId($rawIntermediateStop['extId']);

        if (key_exists('arrTime', $rawIntermediateStop)) {
            $arrival = new DepartureOrArrival();
            $arrival->setStation($station);
            $arrival->setVehicle($vehicle);
            $arrival->setScheduledDateTime($this->parseDateAndTime(
                $rawIntermediateStop['arrDate'],
                $rawIntermediateStop['arrTime']
            ));
            if (key_exists('arrPrognosisType', $rawIntermediateStop)) {
                $arrival->setIsCancelled($this->isArrivalCanceledBasedOnState($rawIntermediateStop['arrPrognosisType']));
                $left = $rawIntermediateStop['arrPrognosisType'] == 'REPORTED';
                $arrival->setIsReported($left);
                if ($left) {
                    $arrival->setStatus(DepartureArrivalState::LEFT);
                }
            }
            if (key_exists('rtArrTime', $rawIntermediateStop)) {
                $arrival->setDelay($this->getSecondsBetweenTwoDatesAndTimes(
                    $rawIntermediateStop['arrDate'],
                    $rawIntermediateStop['arrTime'],
                    $rawIntermediateStop['rtArrDate'],
                    $rawIntermediateStop['rtArrTime']
                ));
            }
            // This first, coarse way of marking a cancelled stop, is set on both completely cancelled stops and on cancelled arrivals/departures
            // If there are signs the arrival is not cancelled, it should be ignored and only affects the departure
            $cancelledVehicleStop = key_exists('cancelled', $rawIntermediateStop) && $rawIntermediateStop['cancelled'] === true
                && !key_exists('rtArrTime', $rawIntermediateStop) && !key_exists('arrPrognosisType', $rawIntermediateStop);
            // The following fields are specific to the departure and are always taken into account
            $cancelledIntermediateStop = key_exists('cancelledArrival', $rawIntermediateStop);
            $cancelledArrival = key_exists('rtAlighting', $rawIntermediateStop) && $rawIntermediateStop['rtAlighting'] === false;
            // Combine all 3 fields
            $arrival->setIsCancelled($cancelledIntermediateStop || $cancelledVehicleStop || $cancelledArrival);

            $arrival->setIsExtra(key_exists('additional', $rawIntermediateStop));
            $arrival->setPlatform($this->parsePlatform($rawIntermediateStop));
            $intermediateStop->setArrival($arrival);
        }

        if (key_exists('depTime', $rawIntermediateStop)) {
            $departure = new DepartureOrArrival();
            $departure->setStation($station);
            $departure->setVehicle($vehicle);
            $departure->setScheduledDateTime($this->parseDateAndTime(
                $rawIntermediateStop['depDate'],
                $rawIntermediateStop['depTime']
            ));
            if (key_exists('depPrognosisType', $rawIntermediateStop)) {
                $departure->setIsCancelled($this->isDepartureCanceledBasedOnState($rawIntermediateStop['depPrognosisType']));
                $left = $rawIntermediateStop['depPrognosisType'] == 'REPORTED';
                $departure->setIsReported($left);
                if ($left) {
                    $departure->setStatus(DepartureArrivalState::LEFT);
                }
            }
            if (key_exists('rtDepTime', $rawIntermediateStop)) {
                $departure->setDelay($this->getSecondsBetweenTwoDatesAndTimes(
                    $rawIntermediateStop['depDate'],
                    $rawIntermediateStop['depTime'],
                    $rawIntermediateStop['rtDepDate'],
                    $rawIntermediateStop['rtDepTime']
                ));
            }
            // This first, coarse way of marking a cancelled stop, is set on both completely cancelled stops and on cancelled arrivals/departures
            // If there are signs the departure is not cancelled, it should be ignored and only affects the arrival
            $cancelledVehicleStop = key_exists('cancelled', $rawIntermediateStop) && $rawIntermediateStop['cancelled'] === true
                && !key_exists('rtDepTime', $rawIntermediateStop) && !key_exists('depPrognosisType', $rawIntermediateStop);
            // The following fields are specific to the departure and are always taken into account
            $cancelledIntermediateStop = key_exists('cancelledDeparture', $rawIntermediateStop);
            $cancelledDeparture = key_exists('rtBoarding', $rawIntermediateStop) && $rawIntermediateStop['rtBoarding'] === false;
            // Combine all 3 fields
            $departure->setIsCancelled($cancelledIntermediateStop || $cancelledVehicleStop || $cancelledDeparture);

            $departure->setIsExtra(key_exists('additional', $rawIntermediateStop));
            $departure->setPlatform($this->parsePlatform($rawIntermediateStop));
            $intermediateStop->setDeparture($departure);
        }

        // Some boolean about scheduled departure? First seen on an added stop
        // dInS, dInR, aOutS, aOutR are not processed at this moment
        return $intermediateStop;
    }


    /**
     * @param DepartureAndArrival[] $parsedIntermediateStops
     * @return void
     */
    public function fixInconsistentReportedStates(array $parsedIntermediateStops): void
    {
        // Sanity check: ensure that the arrived/left status for intermediate stops is correct.
        // If a train has reached the next intermediate stop, it must have passed the previous one.
        // Start at end position minus 2 because we "look forward" in the loop
        for ($i = count($parsedIntermediateStops) - 2; $i >= 0; $i--) {
            if ($parsedIntermediateStops[$i + 1]->getArrival() && $parsedIntermediateStops[$i + 1]->getArrival()->isReported()) {
                $parsedIntermediateStops[$i]->getDeparture()?->setIsReported(true);
                $parsedIntermediateStops[$i]->getDeparture()?->setStatus(DepartureArrivalState::LEFT);
                $parsedIntermediateStops[$i]->getArrival()?->setIsReported(true);
                $parsedIntermediateStops[$i]->getArrival()?->setStatus(DepartureArrivalState::LEFT);
            }
        }
    }

    protected function iRailToHafasId(string $iRailStationId)
    {
        return substr($iRailStationId, 2);
    }
}
