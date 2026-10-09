<?php

declare(strict_types=1);

namespace Matgro\Dify;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Matgro\Dify\Contracts\DifyClientInterface;
use Matgro\Dify\Data\Dataset;
use Matgro\Dify\Data\Document;
use Matgro\Dify\Data\DocumentUploadResult;
use Matgro\Dify\Data\IndexingStatus;
use Matgro\Dify\Data\PaginatedResult;
use Matgro\Dify\Exception\DifyApiException;
use Psr\Http\Message\ResponseInterface;

final class DifyClient implements DifyClientInterface
{
    private ClientInterface $http;
    private string $baseUrl;
    private string $apiKey;
    private float $timeout;
    private float $connectTimeout;
    private int $retries;
    private int $retryDelayMilliseconds;

    public function __construct(
        ClientInterface $http,
        string $baseUrl,
        string $apiKey,
        float $timeout = 30.0,
        float $connectTimeout = 10.0,
        int $retries = 2,
        int $retryDelayMilliseconds = 200
    )
    {
        $this->http = $http;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->retries = $retries;
        $this->retryDelayMilliseconds = $retryDelayMilliseconds;
    }

    public function listDatasets(int $page = 1, int $limit = 20): PaginatedResult
    {
        $response = $this->send('GET', '/v1/datasets', [
            'headers' => $this->headers(),
            'query' => ['page' => $page, 'limit' => $limit],
        ]);

        return PaginatedResult::fromArray($this->decodeResponse($response->getBody()->getContents()), [Dataset::class, 'fromArray']);
    }


    public function createDataset(string $name, ?string $description = null, string $indexingTechnique = 'high_quality', string $permission = 'only_me'): Dataset
    {
        return Dataset::fromArray($this->requestJson('POST', '/v1/datasets', [
            'name' => $name,
            'description' => $description,
            'indexing_technique' => $indexingTechnique,
            'permission' => $permission,
        ]));
    }

    public function updateDataset(string $datasetId, string $name): Dataset
    {
        return Dataset::fromArray($this->requestJson('PATCH', '/v1/datasets/' . rawurlencode($datasetId), ['name' => $name]));
    }

    public function deleteDataset(string $datasetId): void
    {
        $this->send('DELETE', '/v1/datasets/' . rawurlencode($datasetId), ['headers' => $this->headers()]);
    }

    public function listDocuments(string $datasetId, int $page = 1, int $limit = 20, ?string $status = null): PaginatedResult
    {
        $query = ['page' => $page, 'limit' => $limit];
        if ($status !== null) {
            $query['status'] = $status;
        }

        $response = $this->send('GET', '/v1/datasets/' . rawurlencode($datasetId) . '/documents', [
            'headers' => $this->headers(),
            'query' => $query,
        ]);

        return PaginatedResult::fromArray(
            $this->decodeResponse($response->getBody()->getContents()),
            [Document::class, 'fromArray']
        );
    }

    public function getDocument(string $datasetId, string $documentId): Document
    {
        $response = $this->send('GET', '/v1/datasets/' . rawurlencode($datasetId) . '/documents/' . rawurlencode($documentId), ['headers' => $this->headers()]);

        return Document::fromArray($this->decodeResponse($response->getBody()->getContents()));
    }

    public function updateDocumentByText(string $datasetId, string $documentId, string $name, string $text): DocumentUploadResult
    {
        return DocumentUploadResult::fromArray($this->requestJson(
            'POST',
            '/v1/datasets/' . rawurlencode($datasetId) . '/documents/' . rawurlencode($documentId) . '/update-by-text',
            ['name' => $name, 'text' => $text, 'process_rule' => ['mode' => 'automatic']]
        ));
    }


    public function uploadDocument(string $datasetId, string $filePath, ?string $fileName = null): DocumentUploadResult
    {
        $contents = $this->readDocumentFile($filePath);

        $response = $this->send('POST', '/v1/datasets/' . rawurlencode($datasetId) . '/document/create-by-file', [
            'headers' => $this->headers(),
            'multipart' => [
                ['name' => 'file', 'contents' => $contents, 'filename' => $fileName ?: basename($filePath)],
                [
                    'name' => 'data',
                    'contents' => json_encode([
                        'indexing_technique' => 'high_quality',
                        'process_rule' => ['mode' => 'automatic'],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ]);

        return DocumentUploadResult::fromArray($this->decodeResponse($response->getBody()->getContents()));
    }

    public function getIndexingStatus(string $datasetId, string $batchId): IndexingStatus
    {
        $response = $this->send('GET', '/v1/datasets/' . rawurlencode($datasetId) . '/documents/' . rawurlencode($batchId) . '/indexing-status', [
            'headers' => $this->headers(),
        ]);

        return IndexingStatus::fromArray($this->decodeResponse($response->getBody()->getContents()));
    }

    public function deleteDocument(string $datasetId, string $documentId): void
    {
        $this->send('DELETE', '/v1/datasets/' . rawurlencode($datasetId) . '/documents/' . rawurlencode($documentId), [
            'headers' => $this->headers(),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $path, array $payload): array
    {
        $response = $this->send($method, $path, ['headers' => $this->headers(), 'json' => $payload]);

        return $this->decodeResponse($response->getBody()->getContents());
    }

    /** @param array<string, mixed> $options */
    private function send(string $method, string $path, array $options): ResponseInterface
    {
        $options['timeout'] = $this->timeout;
        $options['connect_timeout'] = $this->connectTimeout;

        for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
            try {
                return $this->http->request($method, $this->baseUrl . $path, $options);
            } catch (GuzzleException $exception) {
                $response = method_exists($exception, 'getResponse') ? $exception->getResponse() : null;
                $statusCode = $response === null ? null : $response->getStatusCode();

                if ($attempt < $this->retries && $this->isRetryable($method, $statusCode)) {
                    if ($this->retryDelayMilliseconds > 0) {
                        usleep($this->retryDelayMilliseconds * 1000);
                    }

                    continue;
                }

                $message = $statusCode === null
                    ? 'Dify request failed.'
                    : 'Dify request failed with HTTP status ' . $statusCode . '.';

                throw new DifyApiException($message, $statusCode, $exception);
            }
        }

        throw new DifyApiException('Dify request failed.');
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $this->apiKey];
    }

    /** @return array<string, mixed> */
    private function decodeResponse(string $body): array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new DifyApiException('Dify returned an invalid JSON response.', null, $exception);
        }

        return $decoded;
    }

    private function readDocumentFile(string $filePath): string
    {
        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException('The document file cannot be read.');
        }

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new \InvalidArgumentException('The document file cannot be read.');
        }

        return $contents;
    }

    private function isRetryable(string $method, ?int $statusCode): bool
    {
        return $method === 'GET' && ($statusCode === null || $statusCode === 429 || $statusCode >= 500);
    }
}
