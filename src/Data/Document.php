<?php

declare(strict_types=1);

namespace Matgro\Dify\Data;

final class Document
{
    private string $id;
    private string $name;
    private ?string $indexingStatus;
    private ?int $createdAt;

    private function __construct(string $id, string $name, ?string $indexingStatus, ?int $createdAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->indexingStatus = $indexingStatus;
        $this->createdAt = $createdAt;
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        if (!isset($payload['id'], $payload['name']) || !is_string($payload['id']) || !is_string($payload['name'])) {
            throw new \InvalidArgumentException('Dify document response is missing required fields.');
        }

        return new self(
            $payload['id'],
            $payload['name'],
            isset($payload['indexing_status']) && is_string($payload['indexing_status']) ? $payload['indexing_status'] : null,
            isset($payload['created_at']) && is_int($payload['created_at']) ? $payload['created_at'] : null
        );
    }

    public function id(): string { return $this->id; }
    public function name(): string { return $this->name; }
    public function indexingStatus(): ?string { return $this->indexingStatus; }
    public function createdAt(): ?int { return $this->createdAt; }
}
