<?php
/**
 * Field value object for Super Image Markers.
 *
 * Wraps the selected image ID and ordered marker rows in a Twig-friendly API.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperImageMarkers\fields\data;

use craft\base\Serializable;
use craft\elements\Asset;
use craft\elements\Entry;

/**
 * Normalized value returned by the Super Image Markers field.
 *
 * @author  Amici Infotech
 * @package SuperImageMarkers
 * @since   5.0.0
 */
class ImageMarkersData implements Serializable
{
    /**
     * Creates the field value object.
     *
     * @param ?int $imageId Selected image asset ID.
     * @param MarkerData[] $markers Ordered marker value objects.
     * @param ?Asset $resolvedImage Preloaded image used by processed values.
     * @param bool $processed Whether image/entry access should use preloaded elements.
     */
    public function __construct(
        public readonly ?int $imageId = null,
        private readonly array $markers = [],
        private readonly ?Asset $resolvedImage = null,
        private readonly bool $processed = false,
    ) {
    }

    /**
     * Builds a normalized value object from submitted or stored field data.
     *
     * @param array $value Raw field value array.
     *
     * @return self Normalized field value.
     */
    public static function fromArray(array $value): self
    {
        $imageId = isset($value['imageId']) && is_numeric($value['imageId']) ? (int)$value['imageId'] : null;
        $markers = [];
        $markerRows = is_array($value['markers'] ?? null) ? $value['markers'] : [];

        // Ignore malformed marker rows instead of letting bad submitted JSON break normalization.
        foreach ($markerRows as $marker) {
            if (is_array($marker)) {
                $markers[] = MarkerData::fromArray($marker);
            }
        }

        return new self($imageId, $markers);
    }

    /**
     * Returns the selected image as a query-like reference.
     *
     * @return ElementReference Image reference that supports `.one()`, `.all()`, and `.isEmpty()`.
     */
    public function getImage(): ElementReference
    {
        if ($this->processed) {
            return ElementReference::processed($this->resolvedImage);
        }

        // Restrict lazy image lookups to image assets to match field validation.
        return ElementReference::query(fn() => Asset::find()
            ->id($this->imageId ?: 0)
            ->kind('image'));
    }

    /**
     * Returns all markers as an ordered collection.
     *
     * @return MarkerCollection Marker collection for Twig/PHP access.
     */
    public function getMarkers(): MarkerCollection
    {
        return new MarkerCollection($this->markers);
    }

    /**
     * Serializes the normalized value back to JSON-safe field storage.
     *
     * @return array Field data ready for Craft content storage.
     */
    public function serialize(): array
    {
        return [
            'imageId' => $this->imageId,
            'markers' => array_map(
                static fn(MarkerData $marker): array => $marker->serialize(),
                $this->markers
            ),
        ];
    }

    /**
     * Preloads the selected image and all related marker entries in batches.
     *
     * Existing Twig code can still call `.image.one()` and `.marker.entry.one()`,
     * but those calls will read from already-resolved elements after processing.
     *
     * @return self Processed copy of the field value.
     */
    public function process(): self
    {
        // Resolve the selected image once for the whole value object.
        $image = $this->imageId ? Asset::find()
            ->id($this->imageId)
            ->kind('image')
            ->one() : null;

        // Gather unique entry IDs so marker entry lookups can be batched.
        $entryIds = array_values(array_unique(array_filter(
            array_map(
                static fn(MarkerData $marker): ?int => $marker->entryId,
                $this->markers
            )
        )));
        $entries = [];

        if (!empty($entryIds)) {
            // Key entries by ID so each marker can receive the matching resolved entry.
            foreach (Entry::find()->id($entryIds)->all() as $entry) {
                $entries[$entry->id] = $entry;
            }
        }

        // Preserve marker order while attaching any available resolved entry.
        $markers = array_map(
            static fn(MarkerData $marker): MarkerData => $marker->withResolvedEntry(
                $marker->entryId ? ($entries[$marker->entryId] ?? null) : null
            ),
            $this->markers
        );

        return new self($this->imageId, $markers, $image, true);
    }

    /**
     * Returns marker rows for the Control Panel JavaScript input.
     *
     * @return array<int, array{uid: string, x: float, y: float, entryId: int|null, color: string}>
     */
    public function markerInputRows(): array
    {
        return array_map(
            static fn(MarkerData $marker): array => $marker->serialize(),
            $this->markers
        );
    }
}
