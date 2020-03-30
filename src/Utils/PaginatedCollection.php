<?php

namespace o2o\FluentFM\Utils;

use o2o\FluentFM\Contract;

class PaginatedCollection extends Collection implements Contract\PaginatedCollection
{
    /** @var int */
    private $currentPage;

    /** @var int */
    private $perPage;

    /** @var array */
    private $data;

    /** @var int */
    private $totalCount;

    public function __construct(Collection $collection, int $perPage, int $currentPage)
    {
        $this->totalCount = $collection->getTotalItemCount();
        $this->data = $collection->getData();
        $this->currentPage = $currentPage;
        $this->perPage = $perPage;
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
        return ceil($this->getTotalItemCount() / $this->perPage);
    }

    public function getTotalItemCount(): int
    {
        return $this->totalCount;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
