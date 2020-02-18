<?php

namespace o2o\FluentFM\Connection;

use function sprintf;

class Url
{
    public static function records(string $layout, ?int $id = null): string
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

    public static function container(string $layout, string $field, int $recordId): string
    {
        return sprintf(
            'layouts/%s/records/%s/containers/%s/1',
            $layout,
            $recordId,
            $field
        );
    }
}
