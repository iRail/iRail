<?php

/**
 * An abstract class for a printer. It prints a document
 *
 * @package output
 */
abstract class Printer
{
    protected $documentRoot;
    protected $root;

    /**
     * @param $documentRoot
     */
    public function __construct($documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

    public function printAll()
    {
        $this->printHeader();
        $this->printBody();
    }

    /**
     * prints http header: what kind of output, etc
     *
     * @param string format a mime type
     */
    abstract function printHeader();

    /**
     * prints the body: The idea begind this is a reversed sax-parser. It will create events which you will have to implement in your implementation of an output.
     */
    public function printBody()
    {
        //so that people would know that we have a child of the rootelement
        $this->root = true;
        $this->startRootElement(
            $this->documentRoot->getRootname(),
            $this->documentRoot->version,
            $this->documentRoot->timestamp
        );

        $hash = get_object_vars($this->documentRoot);
        $counter = 0;
        foreach ($hash as $key => $val) {
            if ($key == "version" || $key == "timestamp") {
                $counter++;
                continue;
            }
            $this->printElement($key, $val, true);
            if ($counter < sizeof($hash) - 1) {
                $this->nextObjectElement();
            }
            $counter++;
        }
        $this->endRootElement($this->documentRoot->getRootname());
    }

    /**
     * It will detect what kind of element the element is and will print it accordingly.
     * If it contains more elements it will print more recursively
     *
     * @param $key
     * @param $val
     * @param bool $root
     * @throws Exception
     */
    private function printElement($key, $val, $root = false)
    {
        if (is_array($val)) {
            if (sizeof($val) > 0) {
                $this->startArray($key, sizeof($val), $root);
                foreach ($val as $elementval) {
                    $this->printElement($key, $elementval);
                    if ($val[sizeof($val) - 1] != $elementval) {
                        $this->nextArrayElement();
                    }
                }
                $this->endArray($key, $root);
            } else {
                //very dirty fix of the komma problem when empty array when this would occur
                $this->startKeyVal("empty", "");
                $this->endElement("empty");
            }
        } elseif (is_object($val)) {
            $this->startObject($key, $val);
            $hash = get_object_vars($val);
            $counter = 0;
            foreach ($hash as $elementkey => $elementval) {
                $this->printElement($elementkey, $elementval);
                if ($counter < sizeof($hash) - 1) {
                    $this->nextObjectElement();
                }
                $counter++;
            }
            $this->endObject($key);
        } elseif (is_bool($val)) {
            $val = $val ? 1 : 0;//turn boolean into an int
            $this->startKeyVal($key, $val);
            $this->endElement($key);
        } elseif (!is_null($val)) {
            $this->startKeyVal($key, $val);
            $this->endElement($key);
        } else {
            throw new Exception("Could not retrieve the right information - please report this problem to iRail@list.iRail.be or try again with other arguments.", 500);
        }
    }

    public function nextArrayElement()
    {
    }

    public function nextObjectElement()
    {
    }


    /**
     * @param $name
     * @param $version
     * @param $timestamp
     * @return mixed
     */
    abstract function startRootElement($name, $version, $timestamp);

    /**
     * @param $name
     * @param $number
     * @param bool $root
     * @return mixed
     */
    abstract function startArray($name, $number, $root = false);

    /**
     * @param $name
     * @param $object
     * @return mixed
     */
    abstract function startObject($name, $object);

    /**
     * @param $key
     * @param $val
     * @return mixed
     */
    abstract function startKeyVal($key, $val);

    /**
     * @param $name
     * @param bool $root
     * @return mixed
     */
    abstract function endArray($name, $root = false);

    /**
     * @param $name
     */
    function endObject($name)
    {
        $this->endElement($name);
    }

    /**
     * @param $name
     * @return mixed
     */
    abstract function endElement($name);

    /**
     * @param $name
     * @return mixed
     */
    abstract function endRootElement($name);

    /**
     * @param $ec
     * @param $msg
     * @return mixed
     */
    abstract function printError($ec, $msg);
}
