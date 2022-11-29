<?php

namespace Irail\Repositories\Nmbs\Models\hafas;

/**
 * A hafas remark, typically defined in RemL:
 *
 * "type": "R",
 * "code": "text.realtime.journey.partially.cancelled.between",
 * "icoX": 3,
 * "txtN": "Falen van tussenstops"
 *
 */
class HafasRemark
{
    private string $type, $code, $message;

    /**
     * @param string $type
     * @param string $code
     * @param string $message
     */
    public function __construct(string $type, string $code, string $message)
    {
        $this->type = $type;
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }


}