<?php

namespace Irail\Models;

class MessageLink
{
    private string $link;
    private string $text;

    public function __construct(string $link, string $text)
    {

        $this->link = $link;
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

}