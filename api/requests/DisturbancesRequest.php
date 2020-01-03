<?php

include_once 'Request.php';

class DisturbancesRequest extends Request
{
    // Disturbances don't need a special request. This file makes disturbances fit in nicely with the framework.
    // Currently used fields: format & lang, both implemented in request.

    protected $linebreaks;

    public function __construct()
    {
        parent::__construct();
        parent::setGetVar('linebreaks', '');
    }

    public function getLinebreakCharacter(): string
    {
        return strip_tags(urldecode($this->linebreaks), '<br>');
    }
}
