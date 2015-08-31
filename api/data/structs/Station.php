<?php

/** Copyright (C) 2011 by iRail vzw/asbl
 * This is the Connection class. It contains data.
 *
 * @author pieterc
 */
class Station
{
    private $hafasid;

    public function setHID($id)
    {
        $this->hafasid = $id;
    }
    public function getHID()
    {
        return $this->hafasid;
    }
}
