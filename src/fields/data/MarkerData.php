<?php
/**
 * Single marker value object for Super Image Markers.
 *
 * Stores marker position, color, and the related entry reference.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperImageMarkers\fields\data;

use craft\base\Serializable;
use craft\elements\Entry;

/**
 * Represents one marker stored in the field JSON value.
 *
 * @author  Amici Infotech
 * @package SuperImageMarkers
 * @since   5.0.0
 */
class MarkerData implements Serializable
{
    /**
     * Creates a marker value object.
     *
     * @param string $uid Stable marker ID used by the Control Panel UI.
     * @param float $x Horizontal percentage from the left edge.
     * @param float $y Vertical percentage from the top edge.
     * @param ?int $entryId Related entry ID, if selected.
     * @param string $color Marker hex color.
     * @param ?Entry $resolvedEntry Preloaded entry used by processed values.
     * @param bool $processed Whether entry access should use the preloaded element.
     */
    public function __construct(
        public readonly string $uid,
        public readonly float $x,
        public readonly float $y,
        public readonly ?int $entryId = null,
        public readonly string $color = '#d92828',
        private readonly ?Entry $resolvedEntry = null,
        private readonly bool $processed = false,
    ) {
    }

    /**
     * Builds a marker from the array stored in JSON.
     *
     * @param array $marker Raw marker array from submitted/stored field data.
     *
     * @return self Normalized marker value object.
     */
    public static function fromArray(array $marker): self
    {
        return new self(
            // Generate a UID for legacy or manually-created rows that do not have one.
            (string)($marker['uid'] ?? uniqid('marker-', true)),
            self::normalizePercentage($marker['x'] ?? 50),
            self::normalizePercentage($marker['y'] ?? 50),
            isset($marker['entryId']) && is_numeric($marker['entryId']) ? (int)$marker['entryId'] : null,
            self::normalizeColor($marker['color'] ?? '#d92828'),
        );
    }

    /**
     * Returns the marker itself for the requested `item.marker.x` Twig API.
     *
     * @return self This marker.
     */
    public function getMarker(): self
    {
        return $this;
    }

    /**
     * Returns the related entry as a query-like reference.
     *
     * @return ElementReference Entry reference that supports `.one()`, `.all()`, and `.isEmpty()`.
     */
    public function getEntry(): ElementReference
    {
        if ($this->processed) {
            return ElementReference::processed($this->resolvedEntry);
        }

        // Keep non-processed values lazy so old templates only query when they call `.entry.one()`.
        return ElementReference::query(fn() => Entry::find()->id($this->entryId ?: 0));
    }

    /**
     * Returns a copy of the marker with the related entry already resolved.
     *
     * @param ?Entry $entry Preloaded entry, or null when unavailable.
     *
     * @return self Processed marker copy.
     */
    public function withResolvedEntry(?Entry $entry): self
    {
        return new self(
            $this->uid,
            $this->x,
            $this->y,
            $this->entryId,
            $this->color,
            $entry,
            true,
        );
    }

    /**
     * Serializes the marker back to JSON-safe field storage.
     *
     * @return array Marker data ready for Craft content storage.
     */
    public function serialize(): array
    {
        return [
            'uid' => $this->uid,
            'x' => $this->x,
            'y' => $this->y,
            'entryId' => $this->entryId,
            'color' => $this->color,
        ];
    }

    /**
     * Normalizes percentage coordinates to the valid `0` to `100` range.
     *
     * @param mixed $value Raw coordinate value.
     *
     * @return float Coordinate rounded to two decimal places.
     */
    private static function normalizePercentage(mixed $value): float
    {
        $value = is_numeric($value) ? (float)$value : 0.0;
        $value = max(0.0, min(100.0, $value));

        return round($value, 2);
    }

    /**
     * Normalizes marker colors to six-character hex strings.
     *
     * @param mixed $value Raw color value.
     *
     * @return string Valid lowercase hex color.
     */
    private static function normalizeColor(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return strtolower($value);
        }

        // Use the plugin default color whenever submitted data is missing or invalid.
        return '#d92828';
    }
}
