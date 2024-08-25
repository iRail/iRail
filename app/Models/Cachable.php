<?php

namespace Irail\Models;

use Carbon\Carbon;

interface Cachable
{
    public function getCreatedAt(): ?Carbon;
    public function getExpiresAt(): ?Carbon;
    public function getRemainingTtl(): ?int;
}