<?php

declare(strict_types=1);

namespace Matgro\Dify\Data;

final class Dataset
{
    private string $id;
    private string $name;
    private ?string $description;
    private ?string $permission;

    private function __construct(string $id, string $name, ?string $description, ?string $permission)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->permission = $permission;
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        if (!isset($payload['id'], $payload['name']) || !is_string($payload['id']) || !is_string($payload['name'])) {
            throw new \InvalidArgumentException('Dify dataset response is missing required fields.');
        }

        return new self(
            $payload['id'],
            $payload['name'],
            isset($payload['description']) && is_string($payload['description']) ? $payload['description'] : null,
            isset($payload['permission']) && is_string($payload['permission']) ? $payload['permission'] : null
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function permission(): ?string
    {
        return $this->permission;
    }
}
