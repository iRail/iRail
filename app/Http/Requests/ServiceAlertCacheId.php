<?php

namespace Irail\Http\Requests;

trait ServiceAlertCacheId
{
    public function getCacheId(): string
    {
        return '|ServiceAlerts|' . join('|', [
                $this->getLanguage()
            ]);
    }

    abstract public function getLanguage(): string;
}
