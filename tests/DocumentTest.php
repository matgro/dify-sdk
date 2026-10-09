<?php

declare(strict_types=1);

namespace Matgro\Dify\Tests;

use Matgro\Dify\Data\Document;
use PHPUnit\Framework\TestCase;

final class DocumentTest extends TestCase
{
    public function testCreatesDocumentDtoFromDifyPayload(): void
    {
        $document = Document::fromArray([
            'id' => 'document-123',
            'name' => 'manual.pdf',
            'indexing_status' => 'completed',
            'created_at' => 1720000000,
        ]);

        self::assertSame('document-123', $document->id());
        self::assertSame('manual.pdf', $document->name());
        self::assertSame('completed', $document->indexingStatus());
        self::assertSame(1720000000, $document->createdAt());
    }
}
