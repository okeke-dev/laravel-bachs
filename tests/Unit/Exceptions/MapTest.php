<?php

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Exceptions\BachsApiException;
use OkekeDev\Bachs\Exceptions\BachsAuthenticationException;
use OkekeDev\Bachs\Exceptions\BachsAuthorizationException;
use OkekeDev\Bachs\Exceptions\BachsConflictException;
use OkekeDev\Bachs\Exceptions\BachsNotFoundException;
use OkekeDev\Bachs\Exceptions\BachsRateLimitException;
use OkekeDev\Bachs\Exceptions\BachsValidationException;
use OkekeDev\Bachs\Exceptions\Map;

/**
 * Build a real HTTP client Response so Map::fromResponse can read it.
 *
 * @param  array<mixed>  $body
 * @param  array<string, mixed>  $headers
 */
function mapResponse(int $status, array $body = [], array $headers = []): Response
{
    return new Response(Http::psr7Response($body, $status, $headers));
}

it('maps a 401 response to an authentication exception', function () {
    $response = mapResponse(401, [
        'detail' => 'Invalid API key provided.',
        'error_code' => 'INVALID_API_KEY',
        'doc_url' => 'https://docs.bachs.io/errors#invalid-api-key',
    ], ['x-request-id' => 'req_1']);

    $exception = Map::fromResponse($response);

    expect($exception)->toBeInstanceOf(BachsAuthenticationException::class)
        ->and($exception->status())->toBe(401)
        ->and($exception->errorCode())->toBe('INVALID_API_KEY')
        ->and($exception->requestId())->toBe('req_1')
        ->and($exception->docUrl())->toBe('https://docs.bachs.io/errors#invalid-api-key')
        ->and($exception->getMessage())->toContain('401')
        ->and($exception->getMessage())->toContain('Invalid API key provided.')
        ->and($exception->getMessage())->toContain('req_1');
});

it('maps 403, 404, and 409 responses to their typed exceptions', function () {
    expect(Map::fromResponse(mapResponse(403, ['error_code' => 'NO_ACCESS'])))
        ->toBeInstanceOf(BachsAuthorizationException::class);

    expect(Map::fromResponse(mapResponse(404, ['error_code' => 'NOT_FOUND'])))
        ->toBeInstanceOf(BachsNotFoundException::class);

    expect(Map::fromResponse(mapResponse(409, ['error_code' => 'IDEMPOTENCY_CONFLICT'])))
        ->toBeInstanceOf(BachsConflictException::class);
});

it('maps a 422 response and exposes field errors', function () {
    $response = mapResponse(422, [
        'detail' => 'The given data was invalid.',
        'error_code' => 'VALIDATION_ERROR',
        'errors' => [
            ['field' => 'name', 'message' => 'The name field is required.'],
            ['field' => 'price', 'message' => 'The price must be a decimal string.'],
        ],
    ]);

    $exception = Map::fromResponse($response);

    expect($exception)->toBeInstanceOf(BachsValidationException::class)
        ->and($exception->fieldErrors())->toBe([
            ['field' => 'name', 'message' => 'The name field is required.'],
            ['field' => 'price', 'message' => 'The price must be a decimal string.'],
        ]);
});

it('returns an empty field error list when the 422 payload has none', function () {
    $exception = Map::fromResponse(mapResponse(422, ['error_code' => 'VALIDATION_ERROR']));

    expect($exception->fieldErrors())->toBe([]);
});

it('maps a 429 response and reads Retry-After', function () {
    $response = mapResponse(429, [
        'detail' => 'Too many requests.',
        'error_code' => 'RATE_LIMITED',
    ], ['retry-after' => '12']);

    $exception = Map::fromResponse($response);

    expect($exception)->toBeInstanceOf(BachsRateLimitException::class)
        ->and($exception->retryAfter())->toBe(12);
});

it('falls back to X-RateLimit-Reset for rate limit retry timing', function () {
    $reset = time() + 60;

    $exception = Map::fromResponse(mapResponse(429, [], [
        'x-ratelimit-reset' => (string) $reset,
    ]));

    expect($exception->retryAfter())->toBeBetween(55, 60);
});

it('returns null retry timing when no rate limit headers are present', function () {
    $exception = Map::fromResponse(mapResponse(429));

    expect($exception->retryAfter())->toBeNull();
});

it('falls back to a generic exception for unknown statuses', function () {
    $exception = Map::fromResponse(mapResponse(503, ['detail' => 'Gateway unavailable.']));

    expect($exception)->toBeInstanceOf(BachsApiException::class)
        ->and($exception->status())->toBe(503)
        ->and($exception->errorCode())->toBeNull()
        ->and($exception->getMessage())->toContain('Gateway unavailable.');
});

it('uses a sensible default message when the payload carries none', function () {
    $exception = Map::fromResponse(mapResponse(500));

    expect($exception->getMessage())->toContain('Unknown Bachs API error.');
});
