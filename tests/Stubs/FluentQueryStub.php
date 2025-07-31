<?php

declare(strict_types=1);

namespace Tests\Stubs;

use o2o\FluentFM\Connection\FluentQuery;
use o2o\FluentFM\Contract\FluentFM;

class FluentQueryStub implements FluentFM
{
    use FluentQuery;

    /** @return array<int|string,array|mixed> */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getWithPortals(): bool
    {
        return $this->with_portals;
    }

    public function getWithDeleted(): bool
    {
        return $this->with_deleted;
    }

    /**
     * @inheritDoc
     */
    public function record($layout, $id): FluentFM
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function records($layout, $id = null): FluentFM
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function find(string $layout)
    {
    }

    /**
     * @inheritDoc
     */
    public function findPaginated(string $layout, int $page = 1, int $perPage = 10)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function create(string $layout, array $fields = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function globals(string $layout, array $fields = []): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function update(string $layout, array $fields = [], ?int $recordId = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function upload(string $layout, string $field, string $filename, ?int $recordId = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function download(string $layout, string $field, string $output_dir = './', ?int $recordId = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function delete(string $layout, ?int $recordId = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function softDelete(string $layout, ?int $recordId = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function undelete(string $layout, ?int $recordId = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function fields(string $layout): FluentFM
    {
        return $this;
    }

    public function logout(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function exec()
    {
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
    }

    /**
     * @inheritDoc
     */
    public function latest(string $layout, string $field = 'created_at')
    {
    }

    /**
     * @inheritDoc
     */
    public function lastUpdate(string $layout, string $field = 'updated_at')
    {
    }

    /**
     * @inheritDoc
     */
    public function oldest(string $layout, string $field = 'created_at')
    {
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
    }

    /**
     * @inheritDoc
     */
    public function last()
    {
    }

    /**
     * @inheritDoc
     */
    public function uploadStream(string $layout, string $field, $fileStream, string $filename, ?int $recordId = null)
    {
    }

    public function rawUpdate(string $layout, int $recordId, array $json)
    {
    }

    public function callScript(string $layout, string $scriptName, array $params = []) 
    {
    }
}
