<?php

namespace Irail\Data\Nmbs\Models\hafas;

class HafasResponseContext
{
    private array $locations;
    private array $vehicles;
    private array $remarks;
    private array $messages;

    /**
     * @param HafasLocationDefinition[] $locations
     * @param HafasVehicle[] $vehicles
     * @param HafasRemark[] $remarks
     * @param HafasInformationManagerMessage[] $messages
     */
    public function __construct(array $locations, array $vehicles, array $remarks, array $messages)
    {
        $this->locations = $locations;
        $this->vehicles = $vehicles;
        $this->remarks = $remarks;
        $this->messages = $messages;
    }

    public static function fromJson(array|string $json): HafasResponseContext
    {
        if (is_string($json)) {
            $json = json_decode($json, true);
        }
        $locations = self::parseLocationDefinitions($json);
        $vehicles = self::parseVehicleDefinitions($json);
        $remarks = self::parseRemarkDefinitions($json);
        $messages = self::parseInformationMessageDefinitions($json);
        return new HafasResponseContext($locations, $vehicles, $remarks, $messages);
    }

    /**
     * @return HafasLocationDefinition[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @return HafasLocationDefinition
     */
    public function getLocation(int|string $index): HafasLocationDefinition
    {
        return $this->locations[$index];
    }

    /**
     * @return HafasVehicle[]
     */
    public function getVehicles(): array
    {
        return $this->vehicles;
    }

    /**
     * @return HafasVehicle
     */
    public function getVehicle(int|string $index): HafasVehicle
    {
        return $this->vehicles[$index];
    }

    /**
     * @return HafasRemark[]
     */
    public function getRemarks(): array
    {
        return $this->remarks;
    }

    /**
     * @return HafasInformationManagerMessage[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }


    /**
     * @param $json
     *
     * @return HafasRemark[]
     */
    protected static function parseRemarkDefinitions($json): array
    {
        if (!key_exists('remL', $json['svcResL'][0]['res']['common'])) {
            return [];
        }

        $remarkDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['remL'] as $rawRemark) {
            $remarkType = $rawRemark['type'];
            $remarkCode = $rawRemark['code'];
            $remarkText = strip_tags(preg_replace(
                "/<a href=\".*?\">.*?<\/a>/",
                '',
                $rawRemark['txtN']
            ));
            $remarkDefinitions[] = new HafasRemark($remarkType, $remarkCode, $remarkText);
        }

        return $remarkDefinitions;
    }

    /**
     * Parse the list which contains information about all the service messages which are used in this API response.
     * Service messages warn about service interruptions etc.
     *
     * @param $json
     *
     * @return HafasInformationManagerMessage[]
     */
    protected static function parseInformationMessageDefinitions($json): array
    {
        if (!key_exists('himL', $json['svcResL'][0]['res']['common'])) {
            return [];
        }

        $alertDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['himL'] as $rawAlert) {
            $startDate = \DateTime::createFromFormat("Ymd His", $rawAlert['sDate'] . ' ' . $rawAlert['sTime']);
            $endDate = \DateTime::createFromFormat("Ymd His", $rawAlert['eDate'] . ' ' . $rawAlert['eTime']);
            $modDate = \DateTime::createFromFormat("Ymd His", $rawAlert['lModDate'] . ' ' . $rawAlert['lModTime']);
            $header = $rawAlert['head'];
            $message = $rawAlert['text'];
            $lead = $rawAlert['lead'];
            $publisher = strip_tags($rawAlert['comp']);
            $message = new HafasInformationManagerMessage($startDate, $endDate, $modDate, $header, $lead, $message, $publisher);
            $alertDefinitions[] = $message;
        }
        return $alertDefinitions;
    }

    /**
     * @param $json
     *
     * @return HafasVehicle[]
     */
    protected static function parseVehicleDefinitions($json): array
    {
        if (!key_exists('prodL', $json['svcResL'][0]['res']['common'])) {
            return [];
        }

        $vehicleDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['prodL'] as $rawTrain) {
            $vehicleDisplayName = str_replace(" ", '', $rawTrain['name']);
            $vehicleNumber = trim($rawTrain['number']);
            if (key_exists('prodCtx', $rawTrain)) {
                $vehicleType = trim($rawTrain['prodCtx']['catOutL']);
            } else {
                $vehicleType = trim(str_replace($vehicleNumber, '', $vehicleDisplayName));
            }
            $vehicleDefinitions[] = new HafasVehicle($vehicleNumber, $vehicleDisplayName, $vehicleType);
        }

        return $vehicleDefinitions;
    }

    /**
     * @param $json
     *
     * @return HafasLocationDefinition[]
     */
    protected static function parseLocationDefinitions($json): array
    {
        if (!key_exists('locL', $json['svcResL'][0]['res']['common'])) {
            return [];
        }

        $locationDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['locL'] as $index => $rawLocation) {
            $location = new HafasLocationDefinition($index, $rawLocation['name'], $rawLocation['extId']);
            $locationDefinitions[] = $location;
        }

        return $locationDefinitions;
    }

}