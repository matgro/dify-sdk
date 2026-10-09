<?php

declare(strict_types=1);

namespace Matgro\Dify\Tests;

use Matgro\Dify\Data\DocumentUploadResult;
use PHPUnit\Framework\TestCase;

final class DocumentUploadResultTest extends TestCase
{
    public function testCreatesUploadResultFromDifyPayload(): void
    {
        $result = DocumentUploadResult::fromArray([
            'document' => ['id' => 'document-123', 'name' => 'manual.pdf'],
            'batch' => 'batch-123',
        ]);

        self::assertSame('batch-123', $result->batch());
        self::assertSame('document-123', $result->document()->id());
    }
}
