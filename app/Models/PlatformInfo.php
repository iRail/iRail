<?php

namespace Irail\Models;

class PlatformInfo
{
    private string $id;
    private string $designation;
    private bool $hasChanged;

    /**
     * @param string $parentStopId
     * @param string $designation
     * @param bool   $hasChanged
     */
    public function __construct(string $parentStopId, string $designation, bool $hasChanged)
    {
        $this->id = ($parentStopId && $designation) ? $parentStopId . "#" . $designation : null;
        $this->designation = $designation;
        $this->hasChanged = $hasChanged;
    }

    /**
     * @return string|null
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getDesignation(): string
    {
        return $this->designation;
    }

    /**
     * @return bool
     */
    public function hasChanged(): bool
    {
        return $this->hasChanged;
    }

}
