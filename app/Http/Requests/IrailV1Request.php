<?php

namespace Irail\Http\Requests;

interface IrailV1Request
{
    function getResponseFormat(): string;

    function getLanguage(): string;

    function getUserAgent(): string;

    function isDebugModeEnabled(): bool;
}