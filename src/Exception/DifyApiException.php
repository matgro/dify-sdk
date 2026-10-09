<?php

declare(strict_types=1);

namespace Matgro\Dify\Exception;

use RuntimeException;

final class DifyApiException extends RuntimeException
{
    private ?int $statusCode;

    public function __construct(string $message, ?int $statusCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
