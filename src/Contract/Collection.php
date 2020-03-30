<?php

namespace o2o\FluentFM\Contract;

use ArrayAccess;
use IteratorAggregate;
use Countable;

interface Collection extends Countable, IteratorAggregate, ArrayAccess
{
    public function getTotalItemCount(): int;
    public function getData(): array;
}
