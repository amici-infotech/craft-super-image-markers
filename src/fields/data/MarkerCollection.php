<?php
/**
 * Marker collection for Super Image Markers field values.
 *
 * Provides a small Craft-query-like API for frontend templates.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperImageMarkers\fields\data;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Ordered collection of marker value objects.
 *
 * @author  Amici Infotech
 * @package SuperImageMarkers
 * @since   5.0.0
 */
class MarkerCollection implements Countable, IteratorAggregate
{
    /**
     * Creates the marker collection.
     *
     * @param MarkerData[] $markers Ordered marker value objects.
     */
    public function __construct(private readonly array $markers = [])
    {
    }

    /**
     * Returns every marker in saved order.
     *
     * @return MarkerData[] Ordered marker value objects.
     */
    public function all(): array
    {
        return $this->markers;
    }

    /**
     * Returns the first marker in the collection.
     *
     * @return ?MarkerData The first marker, or null when the collection is empty.
     */
    public function one(): ?MarkerData
    {
        return $this->markers[0] ?? null;
    }

    /**
     * Counts markers in the collection.
     *
     * @return int Number of markers.
     */
    public function count(): int
    {
        return count($this->markers);
    }

    /**
     * Checks whether the collection has no markers.
     *
     * @return bool True when no markers are stored.
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Allows Twig/PHP foreach iteration over the markers.
     *
     * @return Traversable Iterator for the marker array.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->markers);
    }
}
