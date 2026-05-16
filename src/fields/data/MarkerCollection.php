<?php
namespace amici\SuperImageMarkers\fields\data;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class MarkerCollection implements Countable, IteratorAggregate
{
    /**
     * @param MarkerData[] $markers
     */
    public function __construct(private readonly array $markers = [])
    {
    }

    /**
     * @return MarkerData[]
     */
    public function all(): array
    {
        return $this->markers;
    }

    public function one(): ?MarkerData
    {
        return $this->markers[0] ?? null;
    }

    public function count(): int
    {
        return count($this->markers);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->markers);
    }
}
