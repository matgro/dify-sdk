<?php

declare(strict_types=1);

namespace Matgro\Dify\Data;

final class PaginatedResult
{
    /** @var array<int, mixed> */
    private array $items;
    private int $page;
    private int $limit;
    private int $total;
    private bool $hasMore;

    /** @param array<int, mixed> $items */
    private function __construct(array $items, int $page, int $limit, int $total, bool $hasMore)
    {
        $this->items = $items;
        $this->page = $page;
        $this->limit = $limit;
        $this->total = $total;
        $this->hasMore = $hasMore;
    }

    /**
     * @param array<string, mixed> $payload
     * @param callable(array<string, mixed>): mixed $mapper
     */
    public static function fromArray(array $payload, callable $mapper): self
    {
        $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];
        $items = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $items[] = $mapper($item);
            }
        }

        return new self(
            $items,
            isset($payload['page']) && is_int($payload['page']) ? $payload['page'] : 1,
            isset($payload['limit']) && is_int($payload['limit']) ? $payload['limit'] : count($items),
            isset($payload['total']) && is_int($payload['total']) ? $payload['total'] : count($items),
            isset($payload['has_more']) && is_bool($payload['has_more']) ? $payload['has_more'] : false
        );
    }

    /** @return array<int, mixed> */
    public function items(): array { return $this->items; }
    public function page(): int { return $this->page; }
    public function limit(): int { return $this->limit; }
    public function total(): int { return $this->total; }
    public function hasMore(): bool { return $this->hasMore; }
}
