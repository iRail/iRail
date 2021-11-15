<?php

namespace Irail\Data\Nmbs\Models\hafas;

class HafasInformationManagerMessageLink
{
    private string $linkText, $link;

    /**
     * @param string $linkText
     * @param string $link
     */
    public function __construct(string $linkText, string $link)
    {
        $this->linkText = $linkText;
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getLinkText(): string
    {
        return $this->linkText;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }


}