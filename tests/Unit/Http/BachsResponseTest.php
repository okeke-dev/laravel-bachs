<?php

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use OkekeDev\Bachs\Http\BachsResponse;

function bachsResponse(int $status = 200, array $body = [], array $headers = []): BachsResponse
{
    $psrResponse = new PsrResponse(
        $status,
        array_map(fn ($v) => is_array($v) ? $v : [$v], $headers),
        json_encode($body),
    );

    return new BachsResponse(new Response($psrResponse));
}

it('returns the status code', function () {
    expect(bachsResponse(200)->status())->toBe(200)
        ->and(bachsResponse(201)->status())->toBe(201)
        ->and(bachsResponse(404)->status())->toBe(404);
});

it('reports successful responses', function () {
    expect(bachsResponse(200)->successful())->toBeTrue()
        ->and(bachsResponse(201)->successful())->toBeTrue()
        ->and(bachsResponse(204)->successful())->toBeTrue();
});

it('reports failed responses', function () {
    expect(bachsResponse(400)->failed())->toBeTrue()
        ->and(bachsResponse(500)->failed())->toBeTrue();
});

it('reports client errors', function () {
    expect(bachsResponse(400)->clientError())->toBeTrue()
        ->and(bachsResponse(404)->clientError())->toBeTrue()
        ->and(bachsResponse(200)->clientError())->toBeFalse();
});

it('reports server errors', function () {
    expect(bachsResponse(500)->serverError())->toBeTrue()
        ->and(bachsResponse(502)->serverError())->toBeTrue()
        ->and(bachsResponse(200)->serverError())->toBeFalse();
});

it('returns decoded json payload', function () {
    $response = bachsResponse(200, ['id' => 'prod_1', 'name' => 'Test']);

    expect($response->json())->toBe(['id' => 'prod_1', 'name' => 'Test']);
});

it('accesses nested json values by dot notation', function () {
    $response = bachsResponse(200, [
        'price' => ['amount' => '29.00', 'currency' => 'usd'],
    ]);

    expect($response->json('price.amount'))->toBe('29.00')
        ->and($response->json('price.currency'))->toBe('usd');
});

it('returns default for missing json keys', function () {
    $response = bachsResponse(200, ['id' => 'prod_1']);

    expect($response->json('missing', 'fallback'))->toBe('fallback')
        ->and($response->json('missing'))->toBeNull();
});

it('returns the payload as array', function () {
    $response = bachsResponse(200, ['items' => [1, 2, 3]]);

    expect($response->toArray())->toBe(['items' => [1, 2, 3]]);
});

it('returns the request id header', function () {
    $response = bachsResponse(200, [], ['X-Request-Id' => 'req_abc']);

    expect($response->requestId())->toBe('req_abc');
});

it('returns null when no request id header', function () {
    $response = bachsResponse(200, []);

    expect($response->requestId())->toBeNull();
});

it('returns rate limit metadata', function () {
    $response = bachsResponse(200, [], [
        'X-RateLimit-Limit' => '100',
        'X-RateLimit-Remaining' => '98',
        'X-RateLimit-Reset' => '1700000000',
    ]);

    expect($response->rateLimit())->toBe([
        'limit' => '100',
        'remaining' => '98',
        'reset' => '1700000000',
    ]);
});

it('returns null values for missing rate limit headers', function () {
    $response = bachsResponse(200, []);

    expect($response->rateLimit())->toBe([
        'limit' => null,
        'remaining' => null,
        'reset' => null,
    ]);
});

it('returns the raw body', function () {
    $response = bachsResponse(200, ['hello' => 'world']);

    expect($response->body())->toContain('hello')
        ->and($response->body())->toContain('world');
});

it('wraps the underlying Laravel response', function () {
    $response = bachsResponse(200, []);

    expect($response->laravelResponse())->toBeInstanceOf(Response::class);
});

it('handles empty json payload', function () {
    $response = bachsResponse(200, []);

    expect($response->json())->toBe([])
        ->and($response->toArray())->toBe([]);
});

it('handles string header values case-insensitively', function () {
    $response = bachsResponse(200, [], ['x-request-id' => 'req_lower']);

    expect($response->requestId())->toBe('req_lower');
});
