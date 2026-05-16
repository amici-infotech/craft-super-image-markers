<?php
/**
 * Query-like element reference used by Super Image Markers.
 *
 * Gives Twig a familiar `.one()`, `.all()`, and `.isEmpty()` API whether the
 * element is lazy-loaded from a query or already preloaded by `process()`.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperImageMarkers\fields\data;

use Closure;
use craft\base\ElementInterface;

/**
 * Lightweight wrapper around one related Craft element.
 *
 * This is intentionally query-like so existing frontend templates can keep
 * calling `.one()` while processed values can avoid repeated element queries.
 *
 * @author  Amici Infotech
 * @package SuperImageMarkers
 * @since   5.0.0
 */
class ElementReference
{
    /**
     * Creates either a lazy or processed element reference.
     *
     * @param Closure $queryFactory Factory returning a Craft element query when lazy loading is needed.
     * @param ?ElementInterface $element Already-resolved element, when available.
     * @param bool $processed Whether the reference should avoid running the query factory.
     */
    public function __construct(
        private readonly Closure $queryFactory,
        private readonly ?ElementInterface $element = null,
        private readonly bool $processed = false,
    ) {
    }

    /**
     * Creates a reference around an element that has already been resolved.
     *
     * @param ?ElementInterface $element The resolved element, or null if it is unavailable.
     *
     * @return self The processed element reference.
     */
    public static function processed(?ElementInterface $element): self
    {
        return new self(static fn() => null, $element, true);
    }

    /**
     * Creates a lazy reference from a Craft element query factory.
     *
     * @param Closure $queryFactory Factory returning the query to execute for `.one()`.
     *
     * @return self The lazy element reference.
     */
    public static function query(Closure $queryFactory): self
    {
        return new self($queryFactory);
    }

    /**
     * Returns the referenced element.
     *
     * @return ?ElementInterface The element, or null when none is available.
     */
    public function one(): ?ElementInterface
    {
        if ($this->processed) {
            return $this->element;
        }

        // Defer query creation until `.one()` is called so unused references are cheap.
        $query = ($this->queryFactory)();

        return $query?->one();
    }

    /**
     * Returns the referenced element as an array for Craft query compatibility.
     *
     * @return ElementInterface[] One-element array, or an empty array when unavailable.
     */
    public function all(): array
    {
        $element = $this->one();

        return $element ? [$element] : [];
    }

    /**
     * Checks whether the reference resolves to an element.
     *
     * @return bool True when no element is available.
     */
    public function isEmpty(): bool
    {
        return $this->one() === null;
    }
}
