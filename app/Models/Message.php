<?php

namespace Irail\Models;

use Carbon\Carbon;

class Message
{
    private string $id;

    private ?Carbon $validFrom, $validUpTo;
    private ?Carbon $lastModified;
    private string $header;
    private string $leadText;
    private string $message;
    private string $publisher;
    private array $links;
    private MessageType $type;

    /**
     * @param Carbon $validFrom The datetime when this message becomes visible/active
     * @param Carbon $validUpTo The datetime until when this message is visible/active
     * @param Carbon $lastModified The datetime when this message was last updated
     * @param string $header The header text
     * @param string $leadText The lead text
     * @param string $message The complete message text
     * @param string $publisher The name of the organisation who published this message
     */
    public function __construct(
        string $id,
        ?Carbon $validFrom,
        ?Carbon $validUpTo,
        ?Carbon $lastModified,
        MessageType $type,
        string $header,
        string $leadText,
        string $message,
        string $publisher,
        array $links
    )
    {
        $this->id = $id;
        $this->validFrom = $validFrom;
        $this->validUpTo = $validUpTo;
        $this->lastModified = $lastModified;
        $this->header = $header;
        $this->leadText = $leadText;
        $this->message = $message;
        $this->publisher = $publisher;
        $this->links = $links;
        $this->type = $type;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Carbon
     */
    public function getValidFrom(): ?Carbon
    {
        return $this->validFrom;
    }

    /**
     * @return Carbon
     */
    public function getValidUpTo(): ?Carbon
    {
        return $this->validUpTo;
    }

    /**
     * @return Carbon
     */
    public function getLastModified(): ?Carbon
    {
        return $this->lastModified;
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        return strip_tags($this->header);
    }

    /**
     * @return string
     */
    public function getLeadText(): string
    {
        return strip_tags($this->leadText);
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getStrippedMessage(): string
    {
        $messageWithoutLinks = preg_replace("/<a href=\".*?\">.*?<\/a>/", '', $this->message);
        // Replace newline characters with a space to ensure no whitespace is removed.
        $stripped = strip_tags(str_replace('<br>', ' ', $messageWithoutLinks));
        $stripped = str_replace('  ', ' ', $stripped); // Remove double spaces
        return trim($stripped);
    }

    /**
     * @return string
     */
    public function getPublisher(): string
    {
        return $this->publisher;
    }

    public function extractHtmlLink(): ?MessageLink
    {
        preg_match_all("/<a href=\"(.*?)\">(.*?)<\/a>/", urldecode($this->message), $matches);
        if (count($matches[1]) > 1) {
            return new MessageLink(urlencode($matches[1][0]), urlencode($matches[2][0]));
        }
        return null;
    }

    /**
     * @return MessageLink[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @return MessageType
     */
    public function getType(): MessageType
    {
        return $this->type;
    }
}