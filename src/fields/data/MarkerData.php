<?php
namespace amici\SuperImageMarkers\fields\data;

use craft\base\Serializable;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;

class MarkerData implements Serializable
{
    public function __construct(
        public readonly string $uid,
        public readonly float $x,
        public readonly float $y,
        public readonly ?int $entryId = null,
    ) {
    }

    public static function fromArray(array $marker): self
    {
        return new self(
            (string)($marker['uid'] ?? uniqid('marker-', true)),
            self::normalizePercentage($marker['x'] ?? 50),
            self::normalizePercentage($marker['y'] ?? 50),
            isset($marker['entryId']) && is_numeric($marker['entryId']) ? (int)$marker['entryId'] : null,
        );
    }

    public function getMarker(): self
    {
        return $this;
    }

    public function getEntry(): EntryQuery
    {
        return Entry::find()->id($this->entryId ?: 0);
    }

    public function serialize(): array
    {
        return [
            'uid' => $this->uid,
            'x' => $this->x,
            'y' => $this->y,
            'entryId' => $this->entryId,
        ];
    }

    private static function normalizePercentage(mixed $value): float
    {
        $value = is_numeric($value) ? (float)$value : 0.0;
        $value = max(0.0, min(100.0, $value));

        return round($value, 2);
    }
}
