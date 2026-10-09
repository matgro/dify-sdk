<?php

declare(strict_types=1);

namespace Matgro\Dify\Contracts;

use Matgro\Dify\Data\DocumentUploadResult;
use Matgro\Dify\Data\Document;
use Matgro\Dify\Data\Dataset;
use Matgro\Dify\Data\IndexingStatus;
use Matgro\Dify\Data\PaginatedResult;

interface DifyClientInterface
{
    public function listDatasets(int $page = 1, int $limit = 20): PaginatedResult;

    public function createDataset(string $name, ?string $description = null, string $indexingTechnique = 'high_quality', string $permission = 'only_me'): Dataset;

    public function updateDataset(string $datasetId, string $name): Dataset;

    public function deleteDataset(string $datasetId): void;

    public function listDocuments(string $datasetId, int $page = 1, int $limit = 20, ?string $status = null): PaginatedResult;

    public function getDocument(string $datasetId, string $documentId): Document;

    public function updateDocumentByText(string $datasetId, string $documentId, string $name, string $text): DocumentUploadResult;

    public function uploadDocument(string $datasetId, string $filePath, ?string $fileName = null): DocumentUploadResult;

    public function getIndexingStatus(string $datasetId, string $batchId): IndexingStatus;

    public function deleteDocument(string $datasetId, string $documentId): void;
}
