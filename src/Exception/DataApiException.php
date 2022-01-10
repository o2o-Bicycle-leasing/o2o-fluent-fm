<?php

namespace o2o\FluentFM\Exception;

use Illuminate\Support\Facades\Cache;

class DataApiException extends FilemakerException
{
    public static function serviceUnavailable(): DataApiException
    {
        return new self('Filemaker Data API service unavailable', 401);
    }

    public static function connectionRefused(): DataApiException
    {
        return new self('Filemaker Data API refuses connection', 401);
    }

    public static function userCancelledAction(): DataApiException
    {
        Cache::forget('fm_token');
        $message = 'User cancelled Action, forget current token & retry';

        \error_log($message);
        return new self($message, 401);
    }
}
