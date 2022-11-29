<?php


namespace Irail\Repositories\Nmbs\Models\hafas;

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

    /**
     * @param string $number
     * @param string $type
     */
    public function __construct(string $number, string $type)
    {
        $this->number = $number;
        $this->type = $type;
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

    public function getUri(): string
    {
        return "http://irail.be/vehicle/{$this->getType()}{$this->getNumber()}";
    }

    public function toVehicle(): Vehicle
    {
        return new Vehicle($this->getUri(), $this->getId(), $this->getType(), $this->getNumber());
    }
}
