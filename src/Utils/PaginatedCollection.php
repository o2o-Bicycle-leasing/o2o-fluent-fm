<?php

namespace o2o\FluentFM\Utils;

use Illuminate\Support\Collection;

class PaginatedCollection extends Collection
{
    /** @var int */
    private $totalCount;

    /** @var int */
    private $currentPage;

    /** @var int */
    private $perPage;

    /**
     * @param array<mixed, mixed> $items
     */
    public function __construct(
        array $items = [],
        int $totalCount = 0,
        int $perPage,
        int $currentPage
    ) {
        $this->currentPage = $currentPage;
        $this->totalCount = $totalCount;
        $this->perPage = $perPage;

        parent::__construct($items);
    }

    public function getTotalItemCount(): int
    {
        return $this->totalCount;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getItemsPerPage(): int
    {
        return $this->perPage;
    }

    public function getPageCount(): int
    {
        return (int) ceil($this->totalCount / $this->perPage);
    }

    /**
     * @return array<mixed, mixed> $data
     */
    public function getData(): array
    {
        return $this->items;
    }
}
