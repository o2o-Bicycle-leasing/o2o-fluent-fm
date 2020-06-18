<?php

declare(strict_types=1);

namespace o2o\FluentFM\Exception;

class NoResultException extends FilemakerException
{
    /**
     * @param array<string,array|mixed> $query
     */
    public static function noResultForQuery(array $query): NoResultException
    {
        return new self('No results found for query with parameters: ' . json_encode($query));
    }
}
