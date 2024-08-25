<?php

namespace Irail\Repositories\Riv;

class RivCachedException
{
    private string $class;
    private string $message;
    private int $code;

    /**
     * @param string $class
     * @param string $message
     * @param int    $code
     */
    public function __construct(\Exception $e)
    {
        $this->class = get_class($e);
        $this->message = $e->getMessage();
        $this->code = $e->getCode();
    }

    public function toException(): \Exception
    {
        $class = new $this->class($this->message);
        return $class;
    }


}