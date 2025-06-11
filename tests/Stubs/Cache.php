<?php

namespace Illuminate\Support\Facades;

class Cache {
    /** @var array<string, mixed> */
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$cache[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): bool
    {
        self::$cache[$key] = $value;

        return true;
    }

    public static function forget(string $key): void
    {
        unset(self::$cache[$key]);
    }

    public static function flush(): bool
    {
        self::$cache = [];

        return true;
    }
}
