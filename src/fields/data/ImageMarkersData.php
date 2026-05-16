<?php
namespace amici\SuperImageMarkers\fields\data;

use craft\base\Serializable;
use craft\elements\Asset;
use craft\elements\Entry;

class ImageMarkersData implements Serializable
{
    /**
     * @param MarkerData[] $markers
     */
    public function __construct(
        public readonly ?int $imageId = null,
        private readonly array $markers = [],
        private readonly ?Asset $resolvedImage = null,
        private readonly bool $processed = false,
    ) {
    }

    public static function fromArray(array $value): self
    {
        $imageId = isset($value['imageId']) && is_numeric($value['imageId']) ? (int)$value['imageId'] : null;
        $markers = [];
        $markerRows = is_array($value['markers'] ?? null) ? $value['markers'] : [];

        foreach ($markerRows as $marker) {
            if (is_array($marker)) {
                $markers[] = MarkerData::fromArray($marker);
            }
        }

        return new self($imageId, $markers);
    }

    public function getImage(): ElementReference
    {
        if ($this->processed) {
            return ElementReference::processed($this->resolvedImage);
        }

        return ElementReference::query(fn() => Asset::find()
            ->id($this->imageId ?: 0)
            ->kind('image'));
    }

    public function getMarkers(): MarkerCollection
    {
        return new MarkerCollection($this->markers);
    }

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

    public function process(): self
    {
        $image = $this->imageId ? Asset::find()
            ->id($this->imageId)
            ->kind('image')
            ->one() : null;

        $entryIds = array_values(array_unique(array_filter(
            array_map(
                static fn(MarkerData $marker): ?int => $marker->entryId,
                $this->markers
            )
        )));
        $entries = [];

        if (!empty($entryIds)) {
            foreach (Entry::find()->id($entryIds)->all() as $entry) {
                $entries[$entry->id] = $entry;
            }
        }

        $markers = array_map(
            static fn(MarkerData $marker): MarkerData => $marker->withResolvedEntry(
                $marker->entryId ? ($entries[$marker->entryId] ?? null) : null
            ),
            $this->markers
        );

        return new self($this->imageId, $markers, $image, true);
    }

    /**
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
