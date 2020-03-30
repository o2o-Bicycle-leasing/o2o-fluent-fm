<?php


namespace o2o\FluentFM\Contract;


interface PaginatedCollection extends Collection
{
    public function getCurrentPage(): int;
    public function getItemsPerPage(): int;
    public function getPageCount(): int;
}
