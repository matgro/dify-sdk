<?php

declare(strict_types=1);

namespace Matgro\Dify\Tests;

use Matgro\Dify\Data\Document;
use Matgro\Dify\Data\PaginatedResult;
use PHPUnit\Framework\TestCase;

final class PaginatedResultTest extends TestCase
{
    public function testCreatesDocumentPaginationFromDifyPayload(): void
    {
        $result = PaginatedResult::fromArray([
            'data' => [['id' => 'document-123', 'name' => 'manual.pdf']],
            'page' => 1,
            'limit' => 20,
            'total' => 1,
            'has_more' => false,
        ], [Document::class, 'fromArray']);

        self::assertCount(1, $result->items());
        self::assertInstanceOf(Document::class, $result->items()[0]);
        self::assertSame(1, $result->total());
        self::assertFalse($result->hasMore());
    }
}
