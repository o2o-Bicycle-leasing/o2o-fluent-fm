<?php

namespace o2o\FluentFM\Exception;

class DataApiException extends FilemakerException
{
    public static function serviceUnavailable()
    {
        return new self('Filemaker Data API service unavailable', 401);
    }

    public static function connectionRefused()
    {
        return new self('Filemaker Data API refuses connection', 401);
    }
}
