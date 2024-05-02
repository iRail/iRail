<?php

namespace Irail\Http\Requests;

trait ServiceAlertCacheId
{
    public function getCacheId(): string
    {
        return '|ServiceAlerts|' . $this->getLanguage();
    }

    abstract public function getLanguage(): string;
}
