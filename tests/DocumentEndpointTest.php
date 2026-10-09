<?php

declare(strict_types=1);

namespace Matgro\Dify\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Matgro\Dify\DifyClient;
use PHPUnit\Framework\TestCase;

final class DocumentEndpointTest extends TestCase
{
    public function testGetsDocumentById(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([new Response(200, [], json_encode(['id' => 'document-123', 'name' => 'manual.pdf']))]));
        $handler->push(Middleware::history($requests));
        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key');

        self::assertSame('document-123', $client->getDocument('dataset-123', 'document-123')->id());
        self::assertSame('/v1/datasets/dataset-123/documents/document-123', $requests[0]['request']->getUri()->getPath());
    }

    public function testUpdatesTextDocument(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['document' => ['id' => 'document-123', 'name' => 'Manual'], 'batch' => 'batch-123'])),
        ]));
        $handler->push(Middleware::history($requests));
        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key');

        self::assertSame('batch-123', $client->updateDocumentByText('dataset-123', 'document-123', 'Manual', 'Contenido actualizado')->batch());
        self::assertSame('POST', $requests[0]['request']->getMethod());
        self::assertSame('/v1/datasets/dataset-123/documents/document-123/update-by-text', $requests[0]['request']->getUri()->getPath());
    }

    public function testRejectsUnreadableFileWhenUploadingDocument(): void
    {
        $client = new DifyClient(new Client(['handler' => HandlerStack::create(new MockHandler())]), 'https://dify.example.test', 'key');

        $this->expectException(\InvalidArgumentException::class);
        $client->uploadDocument('dataset-123', '/not/a/readable/file.pdf');
    }
}
