<?php

namespace OkekeDev\Bachs\Http;

class BachsRequest
{
    /**
     * @param  array<mixed>  $query
     * @param  array<mixed>  $body
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $body = [],
        public readonly array $headers = [],
        public readonly ?string $idempotencyKey = null,
    ) {}

    /**
     * Whether the request is safe to auto-retry without an idempotency key.
     */
    public function isSafeMethod(): bool
    {
        return in_array($this->method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /**
     * Whether the request carries an idempotency key.
     */
    public function hasIdempotencyKey(): bool
    {
        return $this->idempotencyKey !== null;
    }
}
