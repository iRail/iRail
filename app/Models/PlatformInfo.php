<?php

namespace Irail\Models;

class PlatformInfo
{
    private string $id;
    private string $designation;
    private bool $hasChanged;

    /**
     * @param string $id
     * @param string $designation
     * @param bool   $hasChanged
     */
    public function __construct(string $parentStopId, string $designation, bool $hasChanged)
    {
        $this->id = $parentStopId . "#" . $designation;
        $this->designation = $designation;
        $this->hasChanged = $hasChanged;
    }


}
