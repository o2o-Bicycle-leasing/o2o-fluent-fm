<?php

namespace o2o\FluentFM\Utils;

use ArrayAccess;
use Illuminate\Support\Collection;

class PaginatedCollection implements ArrayAccess, \Iterator
{
    /** @var int */
    private $totalCount;

    /** @var int */
    private $currentPage;

    /** @var int */
    private $perPage;

    /** @var int */
    private $pointer = 0;

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * @param array<mixed, mixed> $items
     */
    public function __construct(
        array $items = [],
        int $totalCount = 0,
        int $perPage = 100,
        int $currentPage = 1
    ) {
        $this->currentPage = $currentPage;
        $this->totalCount = $totalCount;
        $this->perPage = $perPage;
        $this->items = $items;
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
     * @deprecated use all()
     * @return array<mixed, mixed> $data
     */
    public function getData(): array
    {
        return $this->items;
    }

    /**
     * @return array<mixed, mixed> $data
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    public function current()
    {
        return $this->items[$this->pointer];
    }

    public function next()
    {
        $this->pointer++;
    }

    public function key()
    {
        return $this->pointer;
    }

    public function valid()
    {
        return $this->pointer < count($this->items);
    }

    public function rewind()
    {
        $this->pointer = 0;
    }

    /**
     * Run a map over each of the items.
     *
     * @param  callable  $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items), $this->totalCount, $this->perPage, $this->currentPage);
    }
}
