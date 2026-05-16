<?php
namespace amici\SuperImageMarkers\fields\data;

use craft\base\Serializable;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;

class ImageMarkersData implements Serializable
{
    /**
     * @param MarkerData[] $markers
     */
    public function __construct(
        public readonly ?int $imageId = null,
        private readonly array $markers = [],
    ) {
    }

    public static function fromArray(array $value): self
    {
        $imageId = isset($value['imageId']) && is_numeric($value['imageId']) ? (int)$value['imageId'] : null;
        $markers = [];

        foreach (($value['markers'] ?? []) as $marker) {
            if (is_array($marker)) {
                $markers[] = MarkerData::fromArray($marker);
            }
        }

        return new self($imageId, $markers);
    }

    public function getImage(): AssetQuery
    {
        return Asset::find()
            ->id($this->imageId ?: 0)
            ->kind('image');
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

    /**
     * @return array<int, array{uid: string, x: float, y: float, entryId: int|null}>
     */
    public function markerInputRows(): array
    {
        return array_map(
            static fn(MarkerData $marker): array => $marker->serialize(),
            $this->markers
        );
    }
}
