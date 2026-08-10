<?php

namespace OkekeDev\Bachs\Exceptions;

use Throwable;

class BachsApiException extends BachsException
{
    /**
     * @param  array<mixed>  $body  The raw error payload.
     * @param  array<string, string[]>  $headers  The response headers.
     */
    public function __construct(
        public readonly int $status,
        public readonly ?string $errorCode,
        string $message,
        public readonly ?string $requestId = null,
        public readonly array $body = [],
        public readonly array $headers = [],
        public readonly ?string $docUrl = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * The raw error payload.
     *
     * @return array<mixed>
     */
    public function body(): array
    {
        return $this->body;
    }

    public function docUrl(): ?string
    {
        return $this->docUrl;
    }
}
