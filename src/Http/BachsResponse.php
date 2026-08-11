<?php

namespace OkekeDev\Bachs\Http;

use Illuminate\Http\Client\Response;

class BachsResponse
{
    public function __construct(protected readonly Response $response) {}

    public function status(): int
    {
        return $this->response->status();
    }

    public function successful(): bool
    {
        return $this->response->successful();
    }

    public function failed(): bool
    {
        return $this->response->failed();
    }

    public function clientError(): bool
    {
        return $this->response->clientError();
    }

    public function serverError(): bool
    {
        return $this->response->serverError();
    }

    public function body(): string
    {
        return $this->response->body();
    }

    /**
     * The decoded JSON payload, or a value extracted by a dot-notated key.
     *
     * @return array<mixed>|mixed
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $payload = $this->response->json() ?? [];

        return $key === null ? $payload : data_get($payload, $key, $default);
    }

    /**
     * The decoded JSON payload, as an array.
     *
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->response->json() ?? [];
    }

    /**
     * Case-insensitive header lookup.
     */
    public function header(string $name): ?string
    {
        $value = $this->response->header($name);

        return $value === '' ? null : $value;
    }

    /**
     * The x-request-id Bachs attaches to every response, for support.
     */
    public function requestId(): ?string
    {
        return $this->header('x-request-id');
    }

    /**
     * Rate-limit metadata, when present.
     *
     * @return array{limit: string|null, remaining: string|null, reset: string|null}
     */
    public function rateLimit(): array
    {
        return [
            'limit' => $this->header('x-ratelimit-limit'),
            'remaining' => $this->header('x-ratelimit-remaining'),
            'reset' => $this->header('x-ratelimit-reset'),
        ];
    }

    /**
     * The underlying Laravel HTTP client response.
     */
    public function laravelResponse(): Response
    {
        return $this->response;
    }
}
