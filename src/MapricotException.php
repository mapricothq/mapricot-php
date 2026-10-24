<?php

declare(strict_types=1);

namespace Mapricot;

/** An error response from the API, with its HTTP status, error code, and request ID. */
final class MapricotException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly ?string $requestId,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(int $status, string $body, ?string $requestId): self
    {
        $error = json_decode($body, true)['error'] ?? null;
        if (!is_array($error) || !isset($error['code'], $error['message'])) {
            return new self($status, 'unknown', "HTTP $status", $requestId);
        }
        return new self($status, $error['code'], $error['message'], $requestId);
    }

    public function retryable(): bool
    {
        return $this->status === 503 || $this->errorCode === 'concurrency_exceeded';
    }
}
