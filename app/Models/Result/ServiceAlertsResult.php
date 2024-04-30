<?php

namespace Irail\Models\Result;

use Irail\Models\Message;

class ServiceAlertsResult
{
    use Cachable;

    private array $alerts;

    /**
     * @param Message[] $alerts
     */
    public function __construct(array $alerts)
    {
        $this->alerts = $alerts;
    }

    /**
     * @return Message[]
     */
    public function getAlerts(): array
    {
        return $this->alerts;
    }
}
