<?php
namespace amici\SuperImageMarkers\fields\data;

use Closure;
use craft\base\ElementInterface;

class ElementReference
{
    public function __construct(
        private readonly Closure $queryFactory,
        private readonly ?ElementInterface $element = null,
        private readonly bool $processed = false,
    ) {
    }

    public static function processed(?ElementInterface $element): self
    {
        return new self(static fn() => null, $element, true);
    }

    public static function query(Closure $queryFactory): self
    {
        return new self($queryFactory);
    }

    public function one(): ?ElementInterface
    {
        if ($this->processed) {
            return $this->element;
        }

        $query = ($this->queryFactory)();

        return $query?->one();
    }

    /**
     * @return ElementInterface[]
     */
    public function all(): array
    {
        $element = $this->one();

        return $element ? [$element] : [];
    }

    public function isEmpty(): bool
    {
        return $this->one() === null;
    }
}
