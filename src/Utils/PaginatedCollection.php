<?php

namespace o2o\FluentFM\Utils;

class PaginatedCollection
{
    /** @var int */
    private $totalCount;

    /** @var int */
    private $currentPage;

    /** @var int */
    private $perPage;

    /** @var array */
    private $data;

    public function __construct(array $data, int $totalCount, int $perPage, int $currentPage)
    {
        $this->currentPage = $currentPage;
        $this->totalCount = $totalCount;
        $this->perPage = $perPage;
        $this->data = $data;
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
        return ceil($this->totalCount / $this->perPage);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
