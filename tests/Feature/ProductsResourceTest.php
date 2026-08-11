<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Exceptions\BachsNotFoundException;
use OkekeDev\Bachs\Resources\BachsResource;
use OkekeDev\Bachs\Resources\Products;

beforeEach(function () {
    $this->seededClient = BachsResource::defaultClient();
});

afterEach(function () {
    BachsResource::setDefaultClient($this->seededClient);
});

it('seeds the static default client from the configured connection', function () {
    expect(BachsResource::defaultClient()->secret())->toBe('sk_sandbox_test_secret');
});

it('creates a product via the static entry point', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/products' => Http::response(['id' => 'prod_1', 'name' => 'T-shirt'], 201),
    ]);

    $product = Products::create([
        'name' => 'T-shirt',
        'price' => ['amount' => '29.00', 'currency' => 'USD'],
    ]);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/products'
            && $request['name'] === 'T-shirt'
            && $request['price']['amount'] === '29.00'
            && $request['price']['currency'] === 'USD';
    });

    expect($product)->toBe(['id' => 'prod_1', 'name' => 'T-shirt']);
});

it('sends the idempotency key on mutations', function () {
    Http::fake();

    Products::create(['name' => 'T-shirt'], 'idem_create_1');

    Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'idem_create_1'));
});

it('lists products as a paginated collection', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/products*' => Http::response([
            'items' => [
                ['id' => 'prod_1'],
                ['id' => 'prod_2'],
            ],
            'pagination' => [
                'has_more' => false,
                'next_cursor' => null,
                'prev_cursor' => null,
                'limit' => 20,
                'offset' => 0,
                'returned' => 2,
                'total' => 2,
            ],
        ]),
    ]);

    $products = Products::list(['limit' => 20]);

    Http::assertSent(function ($request) {
        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH) === '/v1/products'
            && $request->data() === ['limit' => 20];
    });

    expect($products)->toBeInstanceOf(PaginatedCollection::class)
        ->and($products->count())->toBe(2)
        ->and($products->total())->toBe(2)
        ->and($products->hasMore())->toBeFalse();
});

it('fetches, updates, archives, and unarchives a product', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/products/prod_1' => Http::sequence()
            ->push(['id' => 'prod_1', 'name' => 'T-shirt'], 200)
            ->push(['id' => 'prod_1', 'name' => 'T-shirt', 'price' => ['amount' => '35.00', 'currency' => 'USD']], 200),
        'sandbox-api.bachs.io/v1/products/prod_1/archive' => Http::response(['id' => 'prod_1', 'archived' => true], 200),
        'sandbox-api.bachs.io/v1/products/prod_1/unarchive' => Http::response(['id' => 'prod_1', 'archived' => false], 200),
    ]);

    expect(Products::get('prod_1'))->toBe(['id' => 'prod_1', 'name' => 'T-shirt']);

    $updated = Products::update('prod_1', ['price' => ['amount' => '35.00', 'currency' => 'USD']]);
    expect($updated['price']['amount'])->toBe('35.00');

    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/products/prod_1');

    expect(Products::archive('prod_1')['archived'])->toBeTrue();
    expect(Products::unarchive('prod_1')['archived'])->toBeFalse();
});

it('propagates typed exceptions from the API', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/products/prod_missing' => Http::response([
            'detail' => 'Product not found',
            'error_code' => 'PRODUCT_NOT_FOUND',
            'doc_url' => 'https://docs.bachs.io/api-reference/error-reference#product-not-found',
        ], 404),
    ]);

    Products::get('prod_missing');
})->throws(BachsNotFoundException::class);

it('raises a helpful error when no default client is configured', function () {
    BachsResource::setDefaultClient(null);

    Products::list();
})->throws(BachsInvalidArgumentException::class);
