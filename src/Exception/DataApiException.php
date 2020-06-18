<?php

namespace o2o\FluentFM\Exception;

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
}
