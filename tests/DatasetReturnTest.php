<?php

declare(strict_types=1);

namespace Matgro\Dify\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Matgro\Dify\Data\Dataset;
use Matgro\Dify\DifyClient;
use PHPUnit\Framework\TestCase;

final class DatasetReturnTest extends TestCase
{
    public function testCreateDatasetReturnsDatasetDto(): void
    {
        $client = new DifyClient(new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['id' => 'dataset-123', 'name' => 'Documentos'])),
        ]))]), 'https://dify.example.test', 'key');

        self::assertInstanceOf(Dataset::class, $client->createDataset('Documentos'));
    }
}
