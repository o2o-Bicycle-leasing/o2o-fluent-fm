<?php

namespace o2o\FluentFM\Utils;

use Exception;
use o2o\FluentFM\Contract;
use Traversable;
use ArrayIterator;

class Collection implements Contract\Collection
{
    /** @var int */
    private $totalCount;

    /** @var array */
    private $data;

    public function __construct(array $data, int $totalCount)
    {
        $this->totalCount = $totalCount;
        $this->data = $data;
    }

    public function getTotalItemCount(): int
    {
        return $this->totalCount;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]) || array_key_exists($offset, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->elements[$offset] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;

            return;
        }

        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {

        if (! isset($this->data[$offset]) && ! array_key_exists($offset, $this->data)) {
            return null;
        }

        $removed = $this->data[$offset];
        unset($this->data[$offset]);

        return $removed;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->getData());
    }
}
