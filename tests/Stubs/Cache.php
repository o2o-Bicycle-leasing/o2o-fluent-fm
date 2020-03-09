<?php

declare(strict_types=1);

namespace Illuminate\Support\Facades;

class Cache
{
    public static function has()
    {
        return true;
    }

    public static function get()
    {
        return 'token';
    }
}
