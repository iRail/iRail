<?php

namespace Irail\Data\Nmbs\Models\hafas;

use DateTime;

/**
 * Complex messages published by public transport staff.
 *
 * {
 *      "hid": "45523",
 *      "act": true,
 *      "head": "Reizigersinformatie #MoveSafe",
 *      "lead": "Reizigersinformatie #MoveSafe",
 *      "text": "Het dragen van een mondmasker is verplicht in de stations, op de perrons en in de treinen.",
 *      "icoX": 2,
 *      "prio": 75,
 *      "prod": 65535,
 *      "lModDate": "20210914",
 *      "lModTime": "150059",
 *      "sDate": "20200630",
 *      "sTime": "000500",
 *      "eDate": "20211231",
 *      "eTime": "235900",
 *      "sDaily": "000000",
 *      "eDaily": "235900",
 *      "comp": "SNCB",
 *      "catRefL": [
 *           0
 *      ],
 *      "pubChL": [
 *           {
 *           "name": "timetable",
 *           "fDate": "20200626",
 *           "fTime": "101700",
 *           "tDate": "20211231",
 *           "tTime": "235900"
 *           }
 *      ]
 * },
 */
class HafasInformationManagerMessage
{
    private DateTime $validFrom, $validUpTo;
    private DateTime $lastModified;
    private string $header;
    private string $leadText;
    private string $message;
    private string $publisher;

    /**
     * @param DateTime $validFrom sDate + sTime
     * @param DateTime $validUpTo eDate + eTime
     * @param DateTime $lastModified lModDate + lModTime
     * @param string   $header head
     * @param string   $leadText lead
     * @param string   $message text
     * @param string   $publisher comp
     */
    public function __construct(DateTime $validFrom, DateTime $validUpTo, DateTime $lastModified,
                                string   $header, string $leadText, string $message, string $publisher)
    {
        $this->validFrom = $validFrom;
        $this->validUpTo = $validUpTo;
        $this->lastModified = $lastModified;
        $this->header = $header;
        $this->leadText = $leadText;
        $this->message = $message;
        $this->publisher = $publisher;
    }

    /**
     * @return DateTime
     */
    public function getValidFrom(): DateTime
    {
        return $this->validFrom;
    }

    /**
     * @return DateTime
     */
    public function getValidUpTo(): DateTime
    {
        return $this->validUpTo;
    }

    /**
     * @return DateTime
     */
    public function getLastModified(): DateTime
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
        return strip_tags(preg_replace("/<a href=\".*?\">.*?<\/a>/", '', $this->message));
    }

    /**
     * @return string
     */
    public function getPublisher(): string
    {
        return $this->publisher;
    }

    public function getLink(): ?HafasInformationManagerMessageLink
    {
        preg_match_all("/<a href=\"(.*?)\">(.*?)<\/a>/", urldecode($this->message), $matches);
        if (count($matches[1]) > 1) {
            return new HafasInformationManagerMessageLink(urlencode($matches[2][0]), urlencode($matches[1][0]));
        }
        return null;
    }

}