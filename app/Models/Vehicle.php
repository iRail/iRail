<?php

namespace Irail\Models;

class Vehicle
{
    private string $uri;
    private string $id;

    private string $type;
    private int $number;

    /**
     * @param string $uri
     * @param string $id
     * @param string $type
     * @param int    $number
     */
    public function __construct(string $uri, string $id, string $type, int $number)
    {
        $this->uri = $uri;
        $this->id = $id;
        $this->type = $type;
        $this->number = $number;
    }

    public static function fromTypeAndNumber(string $type, int $number)
    {
        return new Vehicle(
            'http://irail.be/vehicle/' . $type . $number,
            $number,
            $type,
            $number
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
        return $this->type . $this->number;
    }

}
