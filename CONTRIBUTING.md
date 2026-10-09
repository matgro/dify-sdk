# Contributing

## Development

```bash
composer install
composer validate --strict --no-check-publish
composer test
composer audit --locked
```

Keep the SDK independent from application-specific persistence, queues, UI, credentials, and document storage.
Do not add a Dify endpoint to the public contract without first verifying its availability and schema for the supported Dify API.

## Security

Do not commit API keys, Authorization headers, internal URLs, document contents, private dataset identifiers, or logs that may contain them. Report vulnerabilities privately to the maintainers rather than opening a public issue.
