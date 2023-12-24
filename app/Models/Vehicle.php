<?php

namespace Irail\Models;

use Carbon\Carbon;
use Irail\Repositories\Nmbs\Tools\VehicleIdTools;

class Vehicle
{
    private string $uri;
    private string $id;
    private string $type;
    private int $number;
    private Carbon $journeyStartDate;

    private VehicleDirection $direction;

    /**
     * @param string $uri
     * @param string $id
     * @param string $type
     * @param int    $number
     */
    public function __construct(string $uri, string $id, string $type, int $number, Carbon $journeyStartDate = null)
    {
        $this->uri = $uri;
        $this->id = $id;
        $this->type = $type;
        $this->number = $number;
        $this->journeyStartDate = $journeyStartDate ? $journeyStartDate->copy()->startOfDay() : Carbon::now()->startOfDay();
    }

    public static function fromTypeAndNumber(string $type, int $number, Carbon $journeyStartDate = null): Vehicle
    {
        return new Vehicle(
            'http://irail.be/vehicle/' . $type . $number,
            $type . $number,
            $type,
            $number,
            $journeyStartDate
        );
    }

    public static function fromName(string $name, Carbon $journeyStartDate = null): Vehicle
    {
        return Vehicle::fromTypeAndNumber(
            VehicleIdTools::extractTrainType($name),
            VehicleIdTools::extractTrainNumber($name),
            $journeyStartDate
        );
    }


    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    public function getName()
    {
        return $this->type . ' ' . $this->number;
    }

    /**
     * @return VehicleDirection
     */
    public function getDirection(): VehicleDirection
    {
        return $this->direction;
    }

    /**
     * @param VehicleDirection $direction
     */
    public function setDirection(VehicleDirection $direction): void
    {
        $this->direction = $direction;
    }

    public function getJourneyStartDate(): Carbon
    {
        return $this->journeyStartDate;
    }

}
