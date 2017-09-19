<?php

/* Copyright (C) 2011 by iRail vzw/asbl
 *
 * This is an interface to a Request
 *
 * @author pieterc
 */

class Request
{
    public static $SUPPORTED_LANGUAGES = ['EN', 'NL', 'FR', 'DE'];

    private $format = 'xml';
    private $lang = 'EN';
    protected $debug = false;

    /**
     * @param  array , $array
     * @throws Exception
     */
    protected function processRequiredVars($array)
    {
        foreach ($array as $var) {
            if (! isset($this->$var) || $this->$var == '' || is_null($this->$var)) {
                throw new Exception("$var not set. Please review your request and add the right parameters", 400);
            }
        }
    }

    /**
     * will take a get a variable from the GET array and set it as a member variable.
     *
     * @param $varName
     * @param $default
     */
    protected function setGetVar($varName, $default)
    {
        if (isset($_GET[$varName])) {
            $this->$varName = $_GET[$varName];
        } else {
            $this->$varName = $default;
        }
    }

    public function __construct()
    {
        $this->setGetVar('format', 'xml');
        $this->setGetVar('lang', 'EN');
        $this->setGetVar('debug', false);
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return strtolower($this->format);
    }

    /**
     * @return string
     */
    public function getLang()
    {
        $this->lang = strtoupper($this->lang);

        if (in_array($this->lang, self::$SUPPORTED_LANGUAGES)) {
            return $this->lang;
        }

        return 'EN';
    }

    public function isDebug(){
        return $this->debug;
    }
}
