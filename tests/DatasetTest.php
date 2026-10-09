<?php

declare(strict_types=1);

namespace Matgro\Dify\Tests;

use Matgro\Dify\Data\Dataset;
use PHPUnit\Framework\TestCase;

final class DatasetTest extends TestCase
{
    public function testCreatesDatasetDtoFromDifyPayload(): void
    {
        $dataset = Dataset::fromArray([
            'id' => 'dataset-123',
            'name' => 'Documentos Secretaría',
            'description' => 'Material interno',
            'permission' => 'only_me',
        ]);

        self::assertSame('dataset-123', $dataset->id());
        self::assertSame('Documentos Secretaría', $dataset->name());
        self::assertSame('Material interno', $dataset->description());
        self::assertSame('only_me', $dataset->permission());
    }
}
