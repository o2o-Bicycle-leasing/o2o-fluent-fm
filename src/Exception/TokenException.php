<?php

declare(strict_types=1);

namespace o2o\FluentFM\Exception;

class TokenException extends FilemakerException
{
    public static function noTokenReturned()
    {
        return new self('No token returned when sending request to Filemaker');
    }

    public static function unauthorized()
    {
        return new self('Filemaker access unauthorized - please check your credentials', 401);
    }

    public static function invalid()
    {
        return new self('Invalid Filemaker Data API token - please refresh token', 401);
    }
}
