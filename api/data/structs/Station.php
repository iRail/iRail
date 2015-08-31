<?php

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