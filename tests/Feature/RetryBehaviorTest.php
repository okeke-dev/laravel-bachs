<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\BachsClient;
use OkekeDev\Bachs\Exceptions\BachsApiException;
use OkekeDev\Bachs\Exceptions\BachsNetworkException;

function bachsRetryClient(array $overrides = []): BachsClient
{
    return new BachsClient(
        secret: 'sk_sandbox_test_secret',
        baseUrl: 'https://sandbox-api.bachs.io/v1',
        config: array_merge([
            'timeout' => 30,
            'connect_timeout' => 10,
            'retry' => ['times' => 2, 'sleep_ms' => 0],
            'logging' => ['enabled' => false],
        ], $overrides),
    );
}

it('retries on 502 gateway error for safe methods', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts < 3
            ? Http::response(['detail' => 'Bad gateway'], 502)
            : Http::response(['items' => []], 200);
    });

    $response = bachsRetryClient()->get('products');

    expect($attempts)->toBe(3)
        ->and($response->status())->toBe(200);
});

it('retries on 503 service unavailable for safe methods', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts < 2
            ? Http::response(['detail' => 'Service unavailable'], 503)
            : Http::response(['items' => []], 200);
    });

    $response = bachsRetryClient()->get('products');

    expect($attempts)->toBe(2)
        ->and($response->status())->toBe(200);
});

it('retries on 504 gateway timeout for safe methods', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts < 2
            ? Http::response(['detail' => 'Gateway timeout'], 504)
            : Http::response(['items' => []], 200);
    });

    $response = bachsRetryClient()->get('products');

    expect($attempts)->toBe(2)
        ->and($response->status())->toBe(200);
});

it('does not retry on 400 bad request', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response(['detail' => 'Bad request', 'error_code' => 'BAD_REQUEST'], 400);
    });

    expect(fn () => bachsRetryClient()->get('products'))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('does not retry on 403 forbidden', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response(['detail' => 'Forbidden', 'error_code' => 'FORBIDDEN'], 403);
    });

    expect(fn () => bachsRetryClient()->get('products'))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('does not retry on 404 not found', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response(['detail' => 'Not found', 'error_code' => 'NOT_FOUND'], 404);
    });

    expect(fn () => bachsRetryClient()->get('customers/cust_1'))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('does not retry on 409 conflict', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response(['detail' => 'Conflict', 'error_code' => 'CONFLICT'], 409);
    });

    expect(fn () => bachsRetryClient()->post('customers', ['email' => 'a@b.com'], 'idem_1'))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('does not retry on 422 validation error', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response(['detail' => 'Validation error', 'error_code' => 'VALIDATION_ERROR'], 422);
    });

    expect(fn () => bachsRetryClient()->post('customers', [], 'idem_1'))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('does not retry mutation without idempotency key on 429', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response(['detail' => 'Too many requests'], 429);
    });

    expect(fn () => bachsRetryClient()->post('customers', ['email' => 'a@b.com']))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('retries 429 on mutation with idempotency key', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts < 2
            ? Http::response(['detail' => 'Too many requests'], 429, ['Retry-After' => 0])
            : Http::response(['customer_id' => 'cust_123'], 201);
    });

    $response = bachsRetryClient()->post('customers', ['email' => 'a@b.com'], 'idem_1');

    expect($attempts)->toBe(2)
        ->and($response->status())->toBe(201);
});

it('retries connection failures on safe methods', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        if ($attempts < 3) {
            throw new ConnectionException('Connection refused');
        }

        return Http::response(['items' => []], 200);
    });

    $response = bachsRetryClient()->get('products');

    expect($attempts)->toBe(3)
        ->and($response->status())->toBe(200);
});

it('does not retry connection failures on mutation without idempotency key', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        throw new ConnectionException('Connection refused');
    });

    expect(fn () => bachsRetryClient()->post('customers', ['email' => 'a@b.com']))->toThrow(BachsNetworkException::class);

    expect($attempts)->toBe(1);
});

it('does not retry when retry.times is zero', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts === 1
            ? Http::response(['detail' => 'Server error'], 500)
            : Http::response(['items' => []], 200);
    });

    expect(fn () => bachsRetryClient(['retry' => ['times' => 0, 'sleep_ms' => 0]])->get('products'))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('retries exactly the configured number of times', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts < 5
            ? Http::response(['detail' => 'Server error'], 500)
            : Http::response(['items' => []], 200);
    });

    expect(fn () => bachsRetryClient(['retry' => ['times' => 3, 'sleep_ms' => 0]])->get('products'))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(4);
});

it('does not retry DELETE without idempotency key', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response(['detail' => 'Server error'], 500);
    });

    expect(fn () => bachsRetryClient()->delete('customers/cust_1'))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('retries DELETE with idempotency key', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts < 2
            ? Http::response(['detail' => 'Server error'], 500)
            : Http::response([], 204);
    });

    $response = bachsRetryClient()->delete('customers/cust_1', [], 'idem_del_1');

    expect($attempts)->toBe(2)
        ->and($response->status())->toBe(204);
});

it('does not retry PATCH without idempotency key', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response(['detail' => 'Server error'], 500);
    });

    expect(fn () => bachsRetryClient()->patch('customers/cust_1', ['name' => 'New']))->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('retries on 429 with X-RateLimit-Reset header', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts === 1
            ? Http::response(['detail' => 'Rate limited'], 429, ['X-RateLimit-Reset' => (string) time()])
            : Http::response(['items' => []], 200);
    });

    $response = bachsRetryClient()->get('products');

    expect($attempts)->toBe(2)
        ->and($response->status())->toBe(200);
});

it('returns error response details on exhausted retries', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response([
            'detail' => 'Server error',
            'error_code' => 'INTERNAL_SERVER_ERROR',
        ], 500, ['X-Request-Id' => 'req_exhausted']);
    });

    try {
        bachsRetryClient(['retry' => ['times' => 1, 'sleep_ms' => 0]])->get('products');
        $this->fail('Expected exception');
    } catch (BachsApiException $e) {
        expect($attempts)->toBe(2)
            ->and($e->status())->toBe(500)
            ->and($e->errorCode())->toBe('INTERNAL_SERVER_ERROR')
            ->and($e->requestId())->toBe('req_exhausted');
    }
});
