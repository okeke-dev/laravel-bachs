<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\BachsClient;
use OkekeDev\Bachs\Exceptions\BachsApiException;
use OkekeDev\Bachs\Exceptions\BachsAuthenticationException;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Exceptions\BachsNetworkException;
use OkekeDev\Bachs\Exceptions\BachsNotFoundException;
use OkekeDev\Bachs\Exceptions\BachsRateLimitException;
use OkekeDev\Bachs\Exceptions\BachsValidationException;
use OkekeDev\Bachs\Http\BachsResponse;

function bachsTestClient(array $overrides = []): BachsClient
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

it('sends an authenticated request and wraps the response', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/products*' => Http::response(['items' => []], 200, ['X-Request-Id' => 'req_123']),
    ]);

    $response = bachsTestClient()->get('products');

    Http::assertSent(function ($request) {
        return $request->method() === 'GET'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/products'
            && $request->hasHeader('Authorization', 'Bearer sk_sandbox_test_secret');
    });

    expect($response)->toBeInstanceOf(BachsResponse::class)
        ->and($response->status())->toBe(200)
        ->and($response->successful())->toBeTrue()
        ->and($response->requestId())->toBe('req_123')
        ->and($response->json())->toBe(['items' => []]);
});

it('exposes the decoded payload as an array and by dotted key', function () {
    Http::fake([
        '*' => Http::response([
            'id' => 'prod_1',
            'price' => ['amount' => '29.00'],
        ], 200),
    ]);

    $response = bachsTestClient()->get('products/prod_1');

    expect($response->toArray())->toBe([
        'id' => 'prod_1',
        'price' => ['amount' => '29.00'],
    ])->and($response->json('price.amount'))->toBe('29.00')
        ->and($response->json('missing', 'fallback'))->toBe('fallback')
        ->and($response->json('price.missing', null))->toBeNull();
});

it('sends json bodies with an idempotency key on mutations', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers*' => Http::response(['customer_id' => 'cust_123'], 201),
    ]);

    $response = bachsTestClient()->post('customers', ['email' => 'a@b.com'], 'idem_1');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/customers'
            && $request->data() === ['email' => 'a@b.com']
            && $request->hasHeader('Idempotency-Key', 'idem_1');
    });

    expect($response->status())->toBe(201)
        ->and($response->json())->toBe(['customer_id' => 'cust_123']);
});

it('encodes query parameters on requests', function () {
    Http::fake();

    bachsTestClient()->get('products', ['limit' => 20, 'status' => 'active']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'limit=20') && str_contains($request->url(), 'status=active');
    });
});

it('sends per-request custom headers', function () {
    Http::fake();

    bachsTestClient()->request('GET', 'products', ['headers' => ['X-Request-Source' => 'tests']]);

    Http::assertSent(fn ($request) => $request->hasHeader('X-Request-Source', 'tests'));
});

it('sends connection-level default headers on every request', function () {
    Http::fake();

    bachsTestClient(['headers' => ['X-Account' => 'org_123']])->get('products');

    Http::assertSent(fn ($request) => $request->hasHeader('X-Account', 'org_123'));
});

it('lets per-request headers override connection defaults', function () {
    Http::fake();

    bachsTestClient(['headers' => ['X-Account' => 'org_default']])
        ->request('GET', 'products', ['headers' => ['X-Account' => 'org_override']]);

    Http::assertSent(fn ($request) => $request->hasHeader('X-Account', 'org_override'));
});

it('never lets custom headers replace authentication or content negotiation', function () {
    Http::fake();

    bachsTestClient()->request('GET', 'products', [
        'headers' => [
            'Authorization' => 'Bearer sk_live_hacked',
            'Accept' => 'text/html',
            'Content-Type' => 'text/plain',
        ],
    ]);

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer sk_sandbox_test_secret')
            && $request->hasHeader('Accept', 'application/json');
    });
});

it('keeps the idempotency key alongside custom headers', function () {
    Http::fake();

    bachsTestClient()->request('POST', 'customers', [
        'body' => ['email' => 'a@b.com'],
        'headers' => ['X-Trace' => 'abc'],
        'idempotency_key' => 'idem_9',
    ]);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Trace', 'abc')
            && $request->hasHeader('Idempotency-Key', 'idem_9');
    });
});

it('throws when no secret key is configured', function () {
    $client = new BachsClient(secret: '', baseUrl: 'https://api.bachs.io/v1');

    $client->get('customers');
})->throws(BachsInvalidArgumentException::class);

it('maps a 404 response to a typed exception with request context', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers/cust_1*' => Http::response([
            'detail' => 'Customer not found',
            'error_code' => 'NOT_FOUND',
            'doc_url' => 'https://docs.bachs.io/api-reference/error-reference#not-found',
        ], 404, ['X-Request-Id' => 'req_404']),
    ]);

    try {
        bachsTestClient()->get('customers/cust_1');
        $this->fail('Expected a BachsNotFoundException.');
    } catch (BachsNotFoundException $exception) {
        expect($exception->status())->toBe(404)
            ->and($exception->errorCode())->toBe('NOT_FOUND')
            ->and($exception->requestId())->toBe('req_404')
            ->and($exception->docUrl())->toContain('docs.bachs.io')
            ->and($exception->getMessage())->toContain('Customer not found')
            ->and($exception->getMessage())->toContain('req_404');
    }
});

it('maps a 401 response to an authentication exception', function () {
    Http::fake([
        '*' => Http::response(['detail' => 'Invalid API key', 'error_code' => 'UNAUTHORIZED'], 401),
    ]);

    expect(fn () => bachsTestClient()->get('customers'))->toThrow(BachsAuthenticationException::class);
});

it('maps a 422 response to a validation exception with field errors', function () {
    Http::fake([
        '*' => Http::response([
            'detail' => 'Missing required field(s)',
            'error_code' => 'VALIDATION_ERROR',
            'errors' => [
                ['field' => 'amount', 'message' => 'Missing required field', 'type' => 'value_error'],
            ],
        ], 422),
    ]);

    try {
        bachsTestClient()->post('customers', []);
        $this->fail('Expected a BachsValidationException.');
    } catch (BachsValidationException $exception) {
        expect($exception->errorCode())->toBe('VALIDATION_ERROR')
            ->and($exception->fieldErrors())->toHaveCount(1)
            ->and($exception->fieldErrors()[0]['field'])->toBe('amount');
    }
});

it('exposes retry-after on rate limit errors', function () {
    Http::fake([
        '*' => Http::response([
            'detail' => 'Too many requests',
            'error_code' => 'TOO_MANY_REQUESTS',
        ], 429, ['Retry-After' => '3']),
    ]);

    try {
        bachsTestClient(['retry' => ['times' => 0, 'sleep_ms' => 0]])->get('payments');
        $this->fail('Expected a BachsRateLimitException.');
    } catch (BachsRateLimitException $exception) {
        expect($exception->retryAfter())->toBe(3);
    }
});

it('maps an unknown error status to the base api exception', function () {
    Http::fake([
        '*' => Http::response(['detail' => 'Bad request', 'error_code' => 'BAD_REQUEST'], 400),
    ]);

    expect(fn () => bachsTestClient()->get('customers'))->toThrow(BachsApiException::class);
});

it('retries transient 5xx responses for safe methods and succeeds', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts < 3
            ? Http::response(['detail' => 'Server error', 'error_code' => 'INTERNAL_SERVER_ERROR'], 500)
            : Http::response(['items' => []], 200);
    });

    $response = bachsTestClient()->get('products');

    expect($attempts)->toBe(3)
        ->and($response->status())->toBe(200);
});

it('does not retry a mutation without an idempotency key', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return Http::response(['detail' => 'Server error', 'error_code' => 'INTERNAL_SERVER_ERROR'], 500);
    });

    expect(fn () => bachsTestClient()->post('customers', ['email' => 'a@b.com']))
        ->toThrow(BachsApiException::class);

    expect($attempts)->toBe(1);
});

it('retries a mutation that carries an idempotency key', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts < 2
            ? Http::response(['detail' => 'Server error', 'error_code' => 'INTERNAL_SERVER_ERROR'], 500)
            : Http::response(['customer_id' => 'cust_123'], 201);
    });

    $response = bachsTestClient()->post('customers', ['email' => 'a@b.com'], 'idem_1');

    expect($attempts)->toBe(2)
        ->and($response->status())->toBe(201);
});

it('maps connection failures to a network exception', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    expect(fn () => bachsTestClient()->get('customers'))->toThrow(BachsNetworkException::class);
});

it('exposes rate limit headers on successful responses', function () {
    Http::fake([
        '*' => Http::response(['items' => []], 200, [
            'X-RateLimit-Limit' => 100,
            'X-RateLimit-Remaining' => 98,
            'X-RateLimit-Reset' => '1700000000',
        ]),
    ]);

    $response = bachsTestClient()->get('products');

    expect($response->rateLimit())->toBe([
        'limit' => '100',
        'remaining' => '98',
        'reset' => '1700000000',
    ]);
});

it('waits for Retry-After before retrying a rate-limited request', function () {
    $attempts = 0;

    Http::fake(function ($request) use (&$attempts) {
        $attempts++;

        return $attempts === 1
            ? Http::response([
                'detail' => 'Too many requests',
                'error_code' => 'TOO_MANY_REQUESTS',
            ], 429, ['Retry-After' => '1'])
            : Http::response(['items' => []], 200);
    });

    $started = hrtime(true);
    $response = bachsTestClient()->get('products');
    $elapsedMs = (hrtime(true) - $started) / 1e6;

    expect($attempts)->toBe(2)
        ->and($elapsedMs)->toBeGreaterThanOrEqual(900)
        ->and($response->status())->toBe(200);
});
