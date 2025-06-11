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
        parent::__construct(
            array_merge(['host' => 'host', 'file' => 'file', 'user' => 'user', 'pass' => 'pass'], $config),
            $client,
        );
    }

    /** @return array<int|string,mixed|array> */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getToken(bool $force = false): string
    {
        return $this->token = 'token';
    }
}
