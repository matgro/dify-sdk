<?php

declare(strict_types=1);

namespace Matgro\Dify\Data;

final class IndexingStatus
{
    /** @var array<int, Document> */
    private array $items;

    /** @param array<int, Document> $items */
    private function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $items = [];
        $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $items[] = Document::fromArray($item);
            }
        }

        return new self($items);
    }

    /** @return array<int, Document> */
    public function items(): array { return $this->items; }
}
