<?php

declare(strict_types=1);

namespace Matgro\Dify\Data;

final class DocumentUploadResult
{
    private Document $document;
    private string $batch;

    private function __construct(Document $document, string $batch)
    {
        $this->document = $document;
        $this->batch = $batch;
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        if (!isset($payload['document']) || !is_array($payload['document']) || !isset($payload['batch']) || !is_string($payload['batch'])) {
            throw new \InvalidArgumentException('Dify document upload response is missing required fields.');
        }

        return new self(Document::fromArray($payload['document']), $payload['batch']);
    }

    public function document(): Document { return $this->document; }
    public function batch(): string { return $this->batch; }
}
