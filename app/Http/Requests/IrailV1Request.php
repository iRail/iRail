<?php

namespace Irail\Http\Requests;

interface IrailV1Request
{
    public function getResponseFormat(): string;

    public function getLanguage(): string;

    public function getUserAgent(): string;

    public function isDebugModeEnabled(): bool;
}
