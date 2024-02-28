<?php

namespace Irail\Legacy\Output;

use Exception;
use InvalidArgumentException;
use Irail\Http\Responses\v1\DataRoot;

/**
 * An abstract class for a printer. It prints a document.
 */
abstract class Printer
{
    const PRIVATE_VAR_PREFIX = '_';
    protected $documentRoot;
    protected $root;
    private $hash;

    /**
     * Get a new Printer instance for a give format string.
     * @param string        $format The format string.
     * @param DataRoot|null $dataroot The data to pass to the printer.
     * @return Printer The new printer instance.
     * @throws Exception Thrown when the format is invalid.
     */
    public static function getPrinterInstance(string $format, ?DataRoot $dataroot): Printer
    {
        //We're making this in the class form: Json or Xml or Jsonp
        $format = strtolower($format);
        //fallback for when callback is set but not the format= Jsonp
        if (isset($_GET['callback']) && $format == 'Json') {
            $format = 'jsonp';
        }

        switch ($format) {
            case '':
            case 'xml':
                return new Xml($dataroot);
            case 'json':
                return new Json($dataroot);
        }
        throw new InvalidArgumentException('Incorrect format specified. Please correct this and try again', 402);
    }

    /**
     * @param $documentRoot
     */
    public function __construct($documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }


    /**
     * prints the body: The idea begind this is a reversed sax-parser. It will create events which you will have to implement in your implementation of an output.
     * @throws Exception
     */
    public function getBody(): string
    {
        $result = '';
        // Only create this hash once
        $this->hash = get_object_vars($this->documentRoot);
        //so that people would know that we have a child of the rootelement
        $this->root = true;
        $result .= $this->startRootElement(
            strtolower($this->documentRoot->getRootname()),
            $this->documentRoot->version,
            $this->documentRoot->timestamp
        );

        $counter = 0;
        foreach ($this->hash as $key => $val) {
            if ($key == 'version' || $key == 'timestamp' || $this->isPrivateVariableName($key)) {
                $counter++;
                continue;
            }

            $result .= $this->printElement($key, $val, true);
            if ($counter < count($this->hash) - 1) {
                $result .= $this->nextObjectElement();
            }

            $counter++;
        }
        $result .= $this->endRootElement(strtolower($this->documentRoot->getRootname()));
        return $result;
    }

    /**
     * It will detect what kind of element the element is and will print it accordingly.
     * If it contains more elements it will print more recursively.
     *
     * @param      $key
     * @param      $val
     * @param bool $root
     * @throws Exception
     */
    private function printElement($key, $val, $root = false): string
    {
        $result = '';
        if (is_array($val)) {
            $result .= $this->startArray($key, count($val), $root);
            $i = 0;
            foreach ($val as $elementval) {
                $result .= $this->printElement($key, $elementval);
                // Keep count of where we are, and as long as this isn't the last element, print the array divider
                if ($i < (count($val) - 1)) {
                    $result .= $this->nextArrayElement();
                }
                $i++;
            }
            $result .= $this->endArray($key, $root);
        } else if (is_object($val)) {
            $result .= $this->startObject($key, $val);
            $allObjectVars = get_object_vars($val);

            // Remove all keys that won't be printed. If we don't do this before starting the loop, the nextObjectElement
            // logic will fail
            $keysToSkip = [];
            foreach ($allObjectVars as $elementkey => $elementval) {
                if ($this->isPrivateVariableName($elementkey) || is_null($elementval)) {
                    $keysToSkip[] = $elementkey;
                }
            }
            foreach ($keysToSkip as $keyToSkip) {
                unset($allObjectVars[$keyToSkip]);
            }

            $counter = 0;
            foreach ($allObjectVars as $elementkey => $elementval) {
                $result .= $this->printElement($elementkey, $elementval);
                if ($counter < count($allObjectVars) - 1) {
                    $result .= $this->nextObjectElement();
                }
                $counter++;
            }
            $result .= $this->endObject($key);
        } else if (is_bool($val)) {
            $val = $val ? 1 : 0; //turn boolean into an int
            $result .= $this->startKeyVal($key, $val);
            $result .= $this->endElement($key);
        } else if (!is_null($val)) {
            $result .= $this->startKeyVal($key, $val);
            $result .= $this->endElement($key);
        } else {
            throw new Exception(
                'Could not retrieve the right information - please report this problem to iRail@list.iRail.be or try again with other arguments.',
                500
            );
        }
        return $result;
    }

    public function nextArrayElement(): string
    {
        return '';
    }

    public function nextObjectElement(): string
    {
        return '';
    }

    /**
     * @param $name
     * @param $version
     * @param $timestamp
     * @return mixed
     */
    abstract public function startRootElement($name, $version, $timestamp): string;

    /**
     * @param      $name
     * @param      $number
     * @param bool $root
     * @return mixed
     */
    abstract public function startArray($name, $number, $root = false): string;

    /**
     * @param $name
     * @param $object
     * @return mixed
     */
    abstract public function startObject($name, $object): string;

    /**
     * @param $key
     * @param $val
     * @return mixed
     */
    abstract public function startKeyVal($key, $val): string;

    /**
     * @param      $name
     * @param bool $root
     * @return mixed
     */
    abstract public function endArray($name, $root = false): string;

    /**
     * @param $name
     */
    public function endObject($name): string
    {
        return $this->endElement($name);
    }

    /**
     * @param $name
     * @return mixed
     */
    abstract public function endElement($name): string;

    /**
     * @param $name
     * @return mixed
     */
    abstract public function endRootElement($name): string;

    /**
     * @param $ec
     * @param $msg
     * @return mixed
     */
    abstract public function getError($ec, $msg): string;

    private function getCacheHeaders($etag): array
    {
        return ['ETag: "' . $etag . '"', 'Cache-Control: max-age=15'];
    }

    /**
     * @param $elementkey
     * @return bool
     */
    private function isPrivateVariableName($elementkey): bool
    {
        return str_starts_with($elementkey, self::PRIVATE_VAR_PREFIX);
    }


    public abstract function getHeaders(): array;
}
