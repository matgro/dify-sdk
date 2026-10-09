<?php

declare(strict_types=1);

namespace Matgro\Dify\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Matgro\Dify\DifyClient;
use Matgro\Dify\Contracts\DifyClientInterface;
use Matgro\Dify\Exception\DifyApiException;
use PHPUnit\Framework\TestCase;

final class DifyClientTest extends TestCase
{
    public function testClientImplementsItsPublicContract(): void
    {
        self::assertContains(DifyClientInterface::class, class_implements(DifyClient::class));
    }

    public function testCreatesDatasetWithBearerAuthentication(): void
    {
        $requests = [];
        $history = Middleware::history($requests);
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['id' => 'dataset-123', 'name' => 'Documentos Secretaría'])),
        ]));
        $handler->push($history);

        $client = new DifyClient(
            new Client(['handler' => $handler]),
            'https://dify.example.test/',
            'api-key-that-must-not-appear-in-errors'
        );

        self::assertSame('dataset-123', $client->createDataset('Documentos Secretaría', 'Material interno')->id());

        self::assertCount(1, $requests);
        self::assertSame('POST', $requests[0]['request']->getMethod());
        self::assertSame('/v1/datasets', $requests[0]['request']->getUri()->getPath());
        self::assertSame(
            'Bearer api-key-that-must-not-appear-in-errors',
            $requests[0]['request']->getHeaderLine('Authorization')
        );
        self::assertSame(
            [
                'name' => 'Documentos Secretaría',
                'description' => 'Material interno',
                'indexing_technique' => 'high_quality',
                'permission' => 'only_me',
            ],
            json_decode((string) $requests[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testListsDatasetsWithPagination(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['data' => [], 'has_more' => false, 'limit' => 20, 'total' => 0, 'page' => 1])),
        ]));
        $handler->push(Middleware::history($requests));
        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key');

        self::assertSame(0, $client->listDatasets(1, 20)->total());
        self::assertSame('/v1/datasets', $requests[0]['request']->getUri()->getPath());
        self::assertSame('page=1&limit=20', $requests[0]['request']->getUri()->getQuery());
    }


    public function testListsDocumentsWithPaginationAndOptionalStatus(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['data' => [], 'has_more' => false])),
        ]));
        $handler->push(Middleware::history($requests));

        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key');

        $documents = $client->listDocuments('dataset-123', 2, 50, 'completed');
        self::assertSame([], $documents->items());
        self::assertFalse($documents->hasMore());

        self::assertSame(
            '/v1/datasets/dataset-123/documents',
            $requests[0]['request']->getUri()->getPath()
        );
        self::assertSame(
            'page=2&limit=50&status=completed',
            $requests[0]['request']->getUri()->getQuery()
        );
    }

    public function testUpdatesDataset(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['id' => 'dataset-123', 'name' => 'Nuevo nombre'])),
        ]));
        $handler->push(Middleware::history($requests));
        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key');

        self::assertSame('Nuevo nombre', $client->updateDataset('dataset-123', 'Nuevo nombre')->name());
        self::assertSame('PATCH', $requests[0]['request']->getMethod());
        self::assertSame('/v1/datasets/dataset-123', $requests[0]['request']->getUri()->getPath());
        self::assertSame(['name' => 'Nuevo nombre'], json_decode((string) $requests[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testDeletesDataset(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([new Response(204)]));
        $handler->push(Middleware::history($requests));
        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key');

        $client->deleteDataset('dataset-123');

        self::assertSame('DELETE', $requests[0]['request']->getMethod());
        self::assertSame('/v1/datasets/dataset-123', $requests[0]['request']->getUri()->getPath());
    }

    public function testUploadsDocumentAsMultipartData(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['document' => ['id' => 'document-123', 'name' => 'source.txt'], 'batch' => 'batch-123'])),
        ]));
        $handler->push(Middleware::history($requests));
        $file = tempnam(sys_get_temp_dir(), 'dify-sdk-test-');
        file_put_contents($file, 'private document');

        try {
            $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key');

            $result = $client->uploadDocument('dataset-123', $file, 'source.txt');
            self::assertSame('batch-123', $result->batch());
            self::assertSame('document-123', $result->document()->id());

            self::assertSame('POST', $requests[0]['request']->getMethod());
            self::assertSame('/v1/datasets/dataset-123/document/create-by-file', $requests[0]['request']->getUri()->getPath());
            self::assertStringContainsString('multipart/form-data', $requests[0]['request']->getHeaderLine('Content-Type'));
            self::assertStringContainsString('source.txt', (string) $requests[0]['request']->getBody());
            self::assertStringContainsString('"indexing_technique":"high_quality"', (string) $requests[0]['request']->getBody());
        } finally {
            @unlink($file);
        }
    }

    public function testGetsIndexingStatus(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['data' => [['id' => 'document-123', 'name' => 'source.txt', 'indexing_status' => 'completed']]])),
        ]));
        $handler->push(Middleware::history($requests));

        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key');

        $status = $client->getIndexingStatus('dataset-123', 'batch-123');
        self::assertSame('completed', $status->items()[0]->indexingStatus());
        self::assertSame('GET', $requests[0]['request']->getMethod());
        self::assertSame(
            '/v1/datasets/dataset-123/documents/batch-123/indexing-status',
            $requests[0]['request']->getUri()->getPath()
        );
    }

    public function testDeletesDocumentWithoutTryingToDecodeAnEmptyResponse(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([new Response(204)]));
        $handler->push(Middleware::history($requests));

        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key');

        $client->deleteDocument('dataset-123', 'document-123');

        self::assertSame('DELETE', $requests[0]['request']->getMethod());
        self::assertSame(
            '/v1/datasets/dataset-123/documents/document-123',
            $requests[0]['request']->getUri()->getPath()
        );
    }

    public function testSanitizesRemoteErrorsWithoutExposingResponseBodyOrApiKey(): void
    {
        $client = new DifyClient(
            new Client(['handler' => HandlerStack::create(new MockHandler([
                new Response(500, [], 'api-key-that-must-remain-private'),
            ]))]),
            'https://dify.example.test',
            'api-key-that-must-not-appear-in-errors',
            30.0,
            10.0,
            0
        );

        try {
            $client->createDataset('Private');
            self::fail('Expected a sanitized Dify API exception.');
        } catch (DifyApiException $exception) {
            self::assertSame(500, $exception->getStatusCode());
            self::assertStringNotContainsString('api-key-that-must-remain-private', $exception->getMessage());
            self::assertStringNotContainsString('api-key-that-must-not-appear-in-errors', $exception->getMessage());
        }
    }

    public function testAppliesConfiguredTimeoutToRequests(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['id' => 'dataset-123', 'name' => 'Documentos'])),
        ]));
        $handler->push(Middleware::history($requests));

        $client = new DifyClient(
            new Client(['handler' => $handler]),
            'https://dify.example.test',
            'key',
            12.5,
            3.5
        );

        $client->createDataset('Documentos');

        self::assertSame(12.5, $requests[0]['options']['timeout']);
        self::assertSame(3.5, $requests[0]['options']['connect_timeout']);
    }

    public function testRetriesTransientErrorsForSafeReadRequests(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(503, [], 'temporarily unavailable'),
            new Response(200, [], json_encode(['data' => [], 'has_more' => false])),
        ]));
        $handler->push(Middleware::history($requests));

        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key', 30.0, 10.0, 1, 0);

        self::assertSame(0, $client->listDatasets()->total());
        self::assertCount(2, $requests);
    }

    public function testDoesNotRetryWriteRequestsThatCouldCreateDuplicates(): void
    {
        $requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(503, [], 'temporarily unavailable'),
            new Response(200, [], json_encode(['id' => 'dataset-123', 'name' => 'Documentos'])),
        ]));
        $handler->push(Middleware::history($requests));
        $client = new DifyClient(new Client(['handler' => $handler]), 'https://dify.example.test', 'key', 30.0, 10.0, 1, 0);

        $this->expectException(DifyApiException::class);

        try {
            $client->createDataset('Documentos');
        } finally {
            self::assertCount(1, $requests);
        }
    }

    public function testSanitizesInvalidJsonResponses(): void
    {
        $client = new DifyClient(new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], 'not-json'),
        ]))]), 'https://dify.example.test', 'key');

        $this->expectException(DifyApiException::class);
        $this->expectExceptionMessage('Dify returned an invalid JSON response.');

        $client->listDatasets();
    }
}
