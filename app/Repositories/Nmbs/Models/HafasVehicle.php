<?php


namespace Irail\Repositories\Nmbs\Models;

use Carbon\Carbon;
use Irail\Models\Vehicle;

/**
 * A vehicle defined in a Hafas API.
 *
 * {
 *      "name": "IC 545",
 *      "number": "545",
 *      "icoX": 3,
 *      "cls": 4,
 *      "prodCtx": {
 *      "name": "IC   545",
 *      "num": "545",
 *      "catOut": "IC      ",
 *      "catOutS": "007",
 *      "catOutL": "IC ",
 *      "catIn": "007",
 *      "catCode": "2",
 *      "admin": "88____"
 * }
 *
 * OR
 *
 * {
 *      "name": "ICE 10",
 *      "number": "10",
 *      "line": "ICE 10",
 *      "icoX": 0,
 *      "cls": 1
 * },
 */
class HafasVehicle
{
    /**
     * @var string
     */
    private $number;
    /**
     * @var string
     */
    private $type;

    private string $name;

    /**
     * @param string $number
     * @param string $type
     * @param string $name
     */
    public function __construct(string $number, string $type, string $name)
    {
        $this->number = $number;
        $this->type = $type;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->type . $this->number;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function toVehicle(Carbon $journeyStartDate): Vehicle
    {
        return new Vehicle($this->getId(), $this->getType(), $this->getNumber(), $journeyStartDate);
    }
}
