<?php

namespace Irail\Models\Dto\v1;

use Irail\Http\Requests\IrailHttpRequest;
use Irail\Http\Requests\ServiceAlertsV1Request;
use Irail\Models\Message;
use Irail\Models\MessageLink;
use Irail\Models\MessageType;
use Irail\Models\Result\LiveboardSearchResult;
use Irail\Models\Result\ServiceAlertsResult;
use stdClass;

class ServiceAlertsV1Converter extends V1Converter
{

    /**
     * @param IrailHttpRequest      $request
     * @param LiveboardSearchResult $result
     */
    public static function convert(ServiceAlertsV1Request $request,
        ServiceAlertsResult $serviceAlerts): DataRoot
    {
        $result = new DataRoot('disturbances');
        $result->disturbance = array_map(fn($alert) => self::convertDisturbance($alert), $serviceAlerts->getAlerts());

        return $result;
    }

    private static function convertDisturbance(Message $alert): stdClass
    {
        $disturbance = new StdClass();
        $disturbance->title = $alert->getHeader();
        $disturbance->description = strip_tags($alert->getMessage());
        $disturbance->type = $alert->getType() == MessageType::WORKS ? 'planned' : 'disturbance';
        $disturbance->link = $alert->getLinks()[0]->getLink();
        $disturbance->timestamp = $alert->getLastModified()->getTimestamp();
        $disturbance->richtext = $alert->getMessage();
        $disturbance->descriptionLink = array_map(fn($link) => self::convertDisturbanceLink($link), $alert->getLinks());
        return $disturbance;
    }

    private static function convertDisturbanceLink(MessageLink $link): stdClass
    {
        $result = new StdClass();
        $result->link = $link->getLink();
        $result->text = $link->getText();
        return $result;
    }

}