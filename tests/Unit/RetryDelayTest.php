<?php

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use OkekeDev\Bachs\Support\RetryDelay;

function bachsRetryResponse(int $status, array $headers = []): Response
{
    return new Response(new PsrResponse($status, $headers, '{}'));
}

it('grows the delay exponentially from the configured base', function () {
    $retry = ['sleep_ms' => 100, 'multiplier' => 2, 'max_sleep_ms' => 5000];

    expect(RetryDelay::milliseconds(1, null, $retry))->toBe(100)
        ->and(RetryDelay::milliseconds(2, null, $retry))->toBe(200)
        ->and(RetryDelay::milliseconds(3, null, $retry))->toBe(400);
});

it('caps the backoff at the configured maximum', function () {
    $retry = ['sleep_ms' => 100, 'multiplier' => 2, 'max_sleep_ms' => 250];

    expect(RetryDelay::milliseconds(2, null, $retry))->toBe(200)
        ->and(RetryDelay::milliseconds(3, null, $retry))->toBe(250)
        ->and(RetryDelay::milliseconds(10, null, $retry))->toBe(250);
});

it('honors Retry-After seconds on a 429 response', function () {
    $response = bachsRetryResponse(429, ['Retry-After' => 3]);

    expect(RetryDelay::milliseconds(1, $response, []))->toBe(3000);
});

it('derives the delay from the rate limit reset timestamp', function () {
    $response = bachsRetryResponse(429, ['X-RateLimit-Reset' => (string) (time() + 5)]);

    expect(RetryDelay::milliseconds(1, $response, []))->toBe(5000);
});

it('falls back to exponential backoff when a 429 has no reset info', function () {
    $response = bachsRetryResponse(429);

    expect(RetryDelay::milliseconds(2, $response, ['sleep_ms' => 100, 'multiplier' => 2, 'max_sleep_ms' => 5000]))->toBe(200);
});

it('uses exponential backoff for non-rate-limit failures', function () {
    $response = bachsRetryResponse(500);

    expect(RetryDelay::milliseconds(1, $response, ['sleep_ms' => 50, 'multiplier' => 2, 'max_sleep_ms' => 5000]))->toBe(50);
});
