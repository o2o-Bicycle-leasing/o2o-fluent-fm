<?php

declare(strict_types=1);

namespace Tests\Stubs;

use GuzzleHttp\Client;
use o2o\FluentFM\Connection\FluentFMRepository;

class FluentFmRepositoryStub extends FluentFMRepository
{
    public function __construct()
    {
        parent::__construct(['host' => 'host', 'file' => 'file'], null);
    }

    public function getQuery(): array
    {
        return $this->query;
    }
}
