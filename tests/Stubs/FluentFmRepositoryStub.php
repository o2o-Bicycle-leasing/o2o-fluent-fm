<?php

declare(strict_types=1);

namespace Tests\Stubs;

use GuzzleHttp\Client;
use o2o\FluentFM\Connection\FluentFMRepository;

class FluentFmRepositoryStub extends FluentFMRepository
{
    public function getWithDeleted(): bool
    {
        return $this->with_deleted;
    }

    public function __construct(array $config, ?Client $client)
    {
        $this->config = $config;
        parent::__construct(['host' => 'host', 'file' => 'file'], $client);
    }

    /** @return array<int|string,mixed|array> */
    public function getQuery(): array
    {
        return $this->query;
    }
}
