<?php

namespace Irail\Http\Requests;

use DateTime;

trait ServiceAlertCacheId
{
    public function getCacheId(): string
    {
        return '|ServiceAlerts|' . join('|', [
                $this->getLanguage()
            ]);
    }

    abstract function getLanguage(): string;

}