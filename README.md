# Dify SDK for PHP

`matgro/dify-sdk` is a framework-agnostic PHP client for the standard Dify datasets and documents endpoints. It deliberately contains no Laravel configuration, queue, persistence, storage, UI, or credential management.

## Supported API

- Create, list, update, and delete datasets.
- List documents in a dataset.
- Upload a document for asynchronous indexing, or update its text content.
- Read an indexing batch status.
- Delete a document.

The public client implements current documented Dify Knowledge Base endpoints. File-based document update is intentionally omitted because Dify marks that endpoint as deprecated; a future SDK release can model the current general document-update endpoint after its payload is verified.

## Requirements

- PHP 7.4 or later.
- A Dify API key supplied by the consuming application through secure environment configuration.
- `guzzlehttp/guzzle` 7.

## Installation

After a tagged release is registered on Packagist:

```bash
composer require matgro/dify-sdk
```

## Usage

Create the SDK from configuration owned by the consuming application. Never commit an API key or internal Dify URL.

```php
use GuzzleHttp\Client;
use Matgro\Dify\DifyClient;

$client = new DifyClient(
    new Client(),
    getenv('DIFY_BASE_URL'),
    getenv('DIFY_API_KEY')
);

$dataset = $client->createDataset(
    'Documentos Secretaría',
    'Material interno'
);

$client->updateDataset($dataset->id(), 'Documentos institucionales');
$datasets = $client->listDatasets();
$documents = $client->listDocuments($dataset->id());
$upload = $client->uploadDocument($dataset->id(), '/private/path/source.pdf');
$status = $client->getIndexingStatus($dataset->id(), $upload->batch());
$client->updateDocumentByText($dataset->id(), $upload->document()->id(), 'Documento actualizado', 'Contenido actualizado');
$client->deleteDocument($dataset->id(), $upload->document()->id());
$client->deleteDataset($dataset->id());
```

All operations return immutable DTOs. Pagination results provide `items()`, `page()`, `limit()`, `total()`, and `hasMore()`; dataset and document DTOs provide typed accessors such as `id()` and `name()`.

## Errors and retries

Remote HTTP failures are reported as `Matgro\Dify\Exception\DifyApiException`. Its message is sanitized and never contains the API key or remote response body. Invalid JSON responses are also converted to that exception.

The client retries only idempotent `GET` requests for transport failures, HTTP 429, and HTTP 5xx responses. It never automatically retries writes because a timeout after a remote write can otherwise create duplicate datasets/documents. A consuming application should reconcile the remote state before deciding whether to retry a write.

The constructor defaults to a 30-second request timeout, a 10-second connection timeout, two read retries, and a 200 ms retry delay. The final four constructor arguments configure those values.

## Laravel integration

The SDK is framework-agnostic. A Laravel application should bind `DifyClientInterface` in its own service provider and obtain the base URL/API key from server-side configuration. File validation, private storage, queueing, audit records, and deletion consistency remain application responsibilities.

## Development

```bash
composer install
composer validate --strict --no-check-publish
composer test
composer audit --locked
```

## Publishing maintainers checklist

1. Ensure the suite and Composer validation pass.
2. Commit the complete repository and push it to the public GitHub repository for `matgro/dify-sdk`.
3. Verify the GitHub Actions PHP matrix.
4. Create an immutable SemVer Git tag, beginning with `v0.1.0` for the initial public API.
5. Register the repository in Packagist and verify installation in a clean project.

The package archive excludes development dependencies, tests, CI files, and test cache.

## Security

Never commit API keys, Authorization headers, internal URLs, document contents, private dataset identifiers, or application logs. Report vulnerabilities privately to the maintainers rather than opening a public issue.

## License

MIT. See [LICENSE](LICENSE).
