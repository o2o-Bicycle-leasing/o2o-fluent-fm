<?php

namespace o2o\FluentFM\Connection;

use function sprintf;

class Url
{
    /**
     * @param null|string|int $id
     */
    public static function records(string $layout, $id = null): string
    {
        $record = $id ? '/' . $id : '';

        return 'layouts/' . $layout . '/records' . $record;
    }

    public static function find(string $layout): string
    {
        return 'layouts/' . $layout . '/_find';
    }

    public static function globals(): string
    {
        return 'globals';
    }

    /**
     * @param string|int $recordId
     */
    public static function container(string $layout, string $field, $recordId): string
    {
        return sprintf(
            'layouts/%s/records/%s/containers/%s/1',
            $layout,
            $recordId,
            $field
        );
    }
}
