<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\Product;
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
        'sandbox-api.bachs.io/v1/products' => Http::response([
            'id' => 'prod_1',
            'organization_id' => 'org_1',
            'name' => 'T-shirt',
            'price' => ['currency' => 'USD', 'price_type' => 'fixed', 'amount' => '29.00'],
            'status' => 'active',
        ], 201),
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

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->id())->toBe('prod_1')
        ->and($product->name())->toBe('T-shirt')
        ->and($product->price()->amount()->amount())->toBe('29.00')
        ->and($product->isActive())->toBeTrue();
});

it('sends the idempotency key on mutations', function () {
    Http::fake();

    Products::create(['name' => 'T-shirt'], 'idem_create_1');

    Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'idem_create_1'));
});

it('lists products as a paginated collection of DTOs', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/products*' => Http::response([
            'items' => [
                ['id' => 'prod_1', 'name' => 'T-shirt', 'price' => ['currency' => 'USD', 'price_type' => 'fixed', 'amount' => '29.00']],
                ['id' => 'prod_2', 'name' => 'Hoodie', 'price' => ['currency' => 'USD', 'price_type' => 'fixed', 'amount' => '59.00']],
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
        ->and($products->hasMore())->toBeFalse()
        ->and($products->first())->toBeInstanceOf(Product::class)
        ->and($products->first()->name())->toBe('T-shirt')
        ->and($products->last()->name())->toBe('Hoodie');
});

it('fetches, updates, archives, and unarchives a product', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/products/prod_1' => Http::sequence()
            ->push(['id' => 'prod_1', 'name' => 'T-shirt', 'status' => 'active', 'price' => ['currency' => 'USD', 'price_type' => 'fixed', 'amount' => '29.00']], 200)
            ->push(['id' => 'prod_1', 'name' => 'T-shirt', 'status' => 'active', 'price' => ['currency' => 'USD', 'price_type' => 'fixed', 'amount' => '35.00']], 200),
        'sandbox-api.bachs.io/v1/products/prod_1/archive' => Http::response(['id' => 'prod_1', 'status' => 'archived', 'archived_at' => '2026-07-13T14:00:00.000Z'], 200),
        'sandbox-api.bachs.io/v1/products/prod_1/unarchive' => Http::response(['id' => 'prod_1', 'status' => 'active', 'archived_at' => null], 200),
    ]);

    $product = Products::get('prod_1');

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name())->toBe('T-shirt');

    $updated = Products::update('prod_1', ['price' => ['amount' => '35.00', 'currency' => 'USD']]);
    expect($updated->price()->amount()->amount())->toBe('35.00');

    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/products/prod_1');

    expect(Products::archive('prod_1')->isArchived())->toBeTrue();
    expect(Products::unarchive('prod_1')->isActive())->toBeTrue();
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
