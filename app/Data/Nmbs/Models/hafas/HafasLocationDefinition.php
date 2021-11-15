<?php

namespace Irail\Data\Nmbs\Models\hafas;
/**
 * A HAFAS Location definition
 *
 *
 * {
 *      "lid": "A=1@O=Namur@X=4862220@Y=50468794@U=80@L=8863008@",
 *      "type": "S",
 *      "name": "Namur",
 *      "icoX": 1,
 *      "extId": "8863008",
 *      "crd": {
 *          "x": 4862220,
 *          "y": 50468794
 *      },
 *      "pCls": 100,
 *      "rRefL": [
 *          0
 *      ]
 * }
 * S stand for station, P for Point of Interest, A for address
*/

class HafasLocationDefinition
{
    private int $index;
    private string $name;
    private string $extId;

    /**
     * @param int    $index
     * @param string $name
     * @param string $extId
     */
    public function __construct(int $index, string $name, string $extId)
    {
        $this->index = $index;
        $this->name = $name;
        $this->extId = $extId;
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
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
    public function getExtId(): string
    {
        return $this->extId;
    }


}