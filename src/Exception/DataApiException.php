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
        $error = new self('User cancelled Action, forget current token & retry', 401);

        if (function_exists('report')) {
            report($error);
        } else {
            \error_log($error->getMessage());
        }

        return $error;
    }
}
