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

    public function getLanguage(): ?string
    {
        if (str_contains($this->link, '//www.belgiantrain.be/nl')) {
            return 'nl';
        }
        if (preg_match('#/www.belgianrail.be/jp/download/brail_him/\d+_NL#', $this->link)) {
            return 'nl';
        }
        if (str_contains($this->link, '//www.belgiantrain.be/fr')) {
            return 'fr';
        }
        if (preg_match('#/www.belgianrail.be/jp/download/brail_him/\d+_FR#', $this->link)) {
            return 'fr';
        }
        return null;
    }

}