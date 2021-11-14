<?php

namespace Irail\Data\Nmbs\Models;

class IrailStation
{
    private string $id; // "BE.NMBS.007015400"
    private string $uri; //	"http://irail.be/stations/NMBS/007015400"
    private string $name; // "London Saint Pancras International"
    private string $standardname; // "London Saint Pancras International"
    private string $locationX; // "0.12380800"
    private string $locationY; // "51.5304000"

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
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getStandardname(): string
    {
        return $this->standardname;
    }

    /**
     * @return string
     */
    public function getLocationX(): string
    {
        return $this->locationX;
    }

    /**
     * @return string
     */
    public function getLocationY(): string
    {
        return $this->locationY;
    }

    public function getHafasId(): string
    {
        return substr($this->id, 10);
    }

}
