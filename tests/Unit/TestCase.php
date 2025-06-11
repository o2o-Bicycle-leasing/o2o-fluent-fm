<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

class TestCase extends PhpUnitTestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
