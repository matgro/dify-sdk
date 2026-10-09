# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Added

- Typed client for the scoped Dify dataset and document endpoints.
- Immutable dataset, document, upload result, indexing status, and pagination DTOs.
- Sanitized API exceptions, configurable timeouts, and safe read-request retries.
- PHPUnit coverage for the public contract, multipart uploads, errors, and retries.

### Changed

- Aligned the initial public API with current documented Dify Knowledge Base endpoints, while excluding the deprecated file-update endpoint.
