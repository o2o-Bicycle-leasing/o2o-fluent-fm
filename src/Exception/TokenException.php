<?php

declare(strict_types=1);

namespace o2o\FluentFM\Exception;

class TokenException extends FilemakerException
{
    public static function noTokenReturned(): TokenException
    {
        return new self('No token returned when sending request to Filemaker');
    }

    public static function unauthorized(): TokenException
    {
        return new self('Filemaker access unauthorized - please check your credentials', 401);
    }

    public static function invalid(): TokenException
    {
        return new self('Invalid Filemaker Data API token - please refresh token', 401);
    }

    public static function retryFailed(int $retries): TokenException
    {
        return new self(
            'No token returned when sending request to Filemaker, retried ' .
            $retries . ' times without success'
        );
    }
}
