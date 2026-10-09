<?php

declare(strict_types=1);

namespace Matgro\Dify\Tests;

use Matgro\Dify\Data\IndexingStatus;
use PHPUnit\Framework\TestCase;

final class IndexingStatusTest extends TestCase
{
    public function testCreatesIndexingStatusFromDifyPayload(): void
    {
        $status = IndexingStatus::fromArray([
            'data' => [['id' => 'document-123', 'name' => 'manual.pdf', 'indexing_status' => 'completed', 'completed_at' => 1720000000]],
        ]);

        self::assertSame('document-123', $status->items()[0]->id());
        self::assertSame('completed', $status->items()[0]->indexingStatus());
    }
}
