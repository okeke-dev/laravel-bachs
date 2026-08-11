<?php

namespace OkekeDev\Bachs;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Exceptions\BachsNetworkException;
use OkekeDev\Bachs\Exceptions\Map;
use OkekeDev\Bachs\Http\BachsRequest;
use OkekeDev\Bachs\Http\BachsResponse;
use OkekeDev\Bachs\Support\RetryDelay;
use Throwable;

class BachsClient
{
    /**
     * Statuses that are safe to retry on.
     *
     * @var array<int, int>
     */
    protected const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected readonly string $secret,
        protected readonly string $baseUrl,
        protected readonly array $config = [],
    ) {}

    /**
     * The secret key used for authentication.
     */
    public function secret(): string
    {
        return $this->secret;
    }

    /**
     * The base URL all requests are sent to (without trailing slash).
     */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * The raw configuration for this connection.
     *
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * @param  array<mixed>  $query
     */
    public function get(string $path, array $query = []): BachsResponse
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    /**
     * @param  array<mixed>  $body
     */
    public function post(string $path, array $body = [], ?string $idempotencyKey = null): BachsResponse
    {
        return $this->request('POST', $path, ['body' => $body, 'idempotency_key' => $idempotencyKey]);
    }

    /**
     * @param  array<mixed>  $body
     */
    public function patch(string $path, array $body = [], ?string $idempotencyKey = null): BachsResponse
    {
        return $this->request('PATCH', $path, ['body' => $body, 'idempotency_key' => $idempotencyKey]);
    }

    /**
     * @param  array<mixed>  $body
     */
    public function put(string $path, array $body = [], ?string $idempotencyKey = null): BachsResponse
    {
        return $this->request('PUT', $path, ['body' => $body, 'idempotency_key' => $idempotencyKey]);
    }

    /**
     * @param  array<mixed>  $query
     */
    public function delete(string $path, array $query = [], ?string $idempotencyKey = null): BachsResponse
    {
        return $this->request('DELETE', $path, ['query' => $query, 'idempotency_key' => $idempotencyKey]);
    }

    /**
     * Send a multipart request with file attachments (e.g. media uploads).
     *
     * @param  array<int, array{name: string, contents: string, filename?: string}>  $attachments
     * @param  array<mixed>  $body  Additional fields, sent as multipart parts.
     */
    public function upload(string $path, array $attachments, array $body = [], ?string $idempotencyKey = null): BachsResponse
    {
        return $this->request('POST', $path, [
            'attachments' => $attachments,
            'body' => $body,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * Send a request to the Bachs API.
     *
     * Non-2xx responses throw a typed Bachs exception (see Exceptions\Map).
     *
     * @param  array{query?: array<mixed>, body?: array<mixed>, headers?: array<string, string>, attachments?: array<int, array{name: string, contents: string, filename?: string}>, idempotency_key?: string|null}  $options
     */
    public function request(string $method, string $path, array $options = []): BachsResponse
    {
        if ($this->secret === '') {
            throw new BachsInvalidArgumentException('A Bachs secret key must be configured before making requests.');
        }

        $request = new BachsRequest(
            method: strtoupper($method),
            path: $path,
            query: $options['query'] ?? [],
            body: $options['body'] ?? [],
            headers: $options['headers'] ?? [],
            attachments: $options['attachments'] ?? [],
            idempotencyKey: $options['idempotency_key'] ?? null,
        );

        $started = hrtime(true);

        $pending = Http::withToken($this->secret)
            ->acceptJson()
            ->timeout((float) $this->setting('timeout', 30))
            ->connectTimeout((float) $this->setting('connect_timeout', 10))
            ->retry(
                max(1, (int) $this->setting('retry.times', 2) + 1),
                $this->retryDelay(),
                $this->retryWhen($request),
            );

        if ($request->attachments !== []) {
            $pending = $pending->asMultipart();
        }

        try {
            $response = $pending->send($request->method, $this->url($request->path), $this->httpOptions($request));

            $this->log($request, $response->status(), $started, $response);

            if ($response->failed()) {
                throw Map::fromResponse($response);
            }

            return new BachsResponse($response);
        } catch (RequestException $exception) {
            $this->log($request, $exception->response->status(), $started);

            throw Map::fromResponse($exception->response);
        } catch (ConnectionException $exception) {
            $this->log($request, null, $started);

            throw new BachsNetworkException(
                'Connection to Bachs failed: '.$exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Read a value from the connection configuration, using dot notation.
     */
    protected function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Build the retry delay in milliseconds as a function of the attempt and
     * the failure. A 429 response that carries `Retry-After` (or
     * `X-RateLimit-Reset`) is honored verbatim; otherwise the delay grows
     * exponentially from the configured base.
     *
     * @return Closure(int, Throwable): int
     */
    protected function retryDelay(): Closure
    {
        return function (int $attempt, Throwable $exception): int {
            return RetryDelay::milliseconds(
                $attempt,
                $exception instanceof RequestException ? $exception->response : null,
                $this->retryConfiguration(),
            );
        };
    }

    /**
     * The retry configuration for this connection.
     *
     * @return array<string, mixed>
     */
    protected function retryConfiguration(): array
    {
        $retry = $this->setting('retry', []);

        return is_array($retry) ? $retry : [];
    }

    /**
     * Build the full URL for a request path.
     */
    protected function url(string $path): string
    {
        return $this->baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * Build the transport options for a request.
     *
     * @return array{query?: array<mixed>, json?: array<mixed>, multipart?: array<int, array{name: string, contents: mixed, filename?: string}>, headers?: array<string, string>}
     */
    protected function httpOptions(BachsRequest $request): array
    {
        $options = [];

        if ($request->query !== []) {
            $options['query'] = $request->query;
        }

        if ($request->attachments !== []) {
            $options['multipart'] = $this->multipart($request);
        } elseif ($request->body !== []) {
            $options['json'] = $request->body;
        }

        $headers = array_merge(
            $this->sanitizeHeaders($this->defaultHeaders()),
            $this->sanitizeHeaders($request->headers),
        );

        if ($request->idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $request->idempotencyKey;
        }

        if ($headers !== []) {
            $options['headers'] = $headers;
        }

        return $options;
    }

    /**
     * Build the multipart parts: file attachments followed by any additional
     * body fields.
     *
     * @return array<int, array{name: string, contents: mixed, filename?: string}>
     */
    protected function multipart(BachsRequest $request): array
    {
        $parts = [];

        foreach ($request->attachments as $attachment) {
            $part = ['name' => $attachment['name'], 'contents' => $attachment['contents']];

            if (isset($attachment['filename'])) {
                $part['filename'] = $attachment['filename'];
            }

            $parts[] = $part;
        }

        foreach ($request->body as $name => $value) {
            if (is_string($name)) {
                $parts[] = ['name' => $name, 'contents' => (string) $value];
            }
        }

        return $parts;
    }

    /**
     * Connection-level default headers, applied to every request.
     *
     * @return array<mixed>
     */
    protected function defaultHeaders(): array
    {
        $headers = $this->setting('headers', []);

        return is_array($headers) ? $headers : [];
    }

    /**
     * Drop reserved transport headers so callers cannot override
     * authentication, content negotiation, or the idempotency key.
     *
     * @param  array<mixed>  $headers
     * @return array<string, string>
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $name => $value) {
            if (! is_string($name)) {
                continue;
            }

            if (in_array(strtolower($name), ['authorization', 'accept', 'content-type', 'idempotency-key'], true)) {
                continue;
            }

            $sanitized[$name] = (string) $value;
        }

        return $sanitized;
    }

    /**
     * Decide whether a failed attempt should be retried.
     *
     * Safe methods (GET/HEAD/OPTIONS) and requests carrying an idempotency key
     * may be retried on 429/5xx or network failures. Mutations without an
     * idempotency key are never blind-retried, to avoid double side effects.
     */
    protected function retryWhen(BachsRequest $request): Closure
    {
        return function (Throwable $exception) use ($request): bool {
            if ($exception instanceof RequestException && $exception->response !== null) {
                return in_array($exception->response->status(), self::RETRYABLE_STATUSES, true)
                    && $this->canAutoRetry($request);
            }

            if ($exception instanceof ConnectionException) {
                return $this->canAutoRetry($request);
            }

            return false;
        };
    }

    /**
     * Whether a request may be retried without risking duplicate side effects.
     */
    protected function canAutoRetry(BachsRequest $request): bool
    {
        return $request->isSafeMethod() || $request->hasIdempotencyKey();
    }

    /**
     * Log safe request metadata only. Never log secrets, bodies, or headers.
     */
    protected function log(BachsRequest $request, ?int $status, int $started, ?Response $response = null): void
    {
        if (! $this->setting('logging.enabled', true)) {
            return;
        }

        $context = [
            'method' => $request->method,
            'path' => $request->path,
            'status' => $status,
            'duration_ms' => (int) round((hrtime(true) - $started) / 1e6),
            'request_id' => $response?->header('x-request-id') ?: null,
        ];

        Log::channel($this->setting('logging.channel'))->info('Bachs HTTP request', $context);
    }
}
