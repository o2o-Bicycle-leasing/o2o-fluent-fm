<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Stubs\FluentFmRepositoryStub;

class FluentFmRepositoryTest extends TestCase
{
    public function testInitialQueryIsCleared()
    {
        $repo = new FluentFmRepositoryStub([]);
        $this->assertEquals([
            'limit' => null,
            'offset' => null,
            'sort' => null,
            'query' => null,
            'script' => null,
            'script.param' => null,
            'script.prerequest' => null,
            'script.prerequest.param' => null,
            'script.presort' => null,
            'script.presort.param' => null
        ], $repo->getQuery());
    }

    public function testHeaders()
    {
        $repo = new FluentFmRepositoryStub([]);
        $this->assertEquals([
            'Authorization' => 'Bearer token',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'cache-control' => 'no-cache',
            'read_timeout' => 30000,
        ], $repo->getClientHeaders());
    }
}
