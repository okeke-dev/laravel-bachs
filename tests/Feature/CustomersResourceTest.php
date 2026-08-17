<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\Customer;
use OkekeDev\Bachs\Dto\PortalSession;
use OkekeDev\Bachs\Resources\Customers;

it('creates a customer', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers' => Http::response([
            'customer_id' => 'cust_1',
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'phone_number' => '+2348012345678',
            'metadata' => [],
            'billing_address' => [],
            'created_at' => '2026-01-15T10:00:00Z',
            'updated_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);

    $customer = Customers::create(['email' => 'jane@example.com', 'name' => 'Jane Doe']);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/customers'
            && $request['email'] === 'jane@example.com';
    });

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->id())->toBe('cust_1')
        ->and($customer->email())->toBe('jane@example.com')
        ->and($customer->name())->toBe('Jane Doe')
        ->and($customer->phoneNumber())->toBe('+2348012345678');
});

it('lists customers as a paginated collection of DTOs', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers*' => Http::response([
            'items' => [
                ['customer_id' => 'cust_1', 'email' => 'jane@example.com', 'name' => 'Jane Doe'],
                ['customer_id' => 'cust_2', 'email' => 'john@example.com', 'name' => 'John Smith'],
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

    $customers = Customers::list();

    expect($customers)->toBeInstanceOf(PaginatedCollection::class)
        ->and($customers->count())->toBe(2)
        ->and($customers->total())->toBe(2)
        ->and($customers->first())->toBeInstanceOf(Customer::class)
        ->and($customers->first()->email())->toBe('jane@example.com');
});

it('fetches and updates a customer', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers/cust_1' => Http::sequence()
            ->push([
                'customer_id' => 'cust_1',
                'email' => 'jane@example.com',
                'name' => 'Jane Doe',
                'created_at' => '2026-01-15T10:00:00Z',
                'updated_at' => '2026-01-15T10:00:00Z',
            ], 200)
            ->push([
                'customer_id' => 'cust_1',
                'email' => 'jane@example.com',
                'name' => 'Jane Smith',
                'created_at' => '2026-01-15T10:00:00Z',
                'updated_at' => '2026-01-16T10:00:00Z',
            ], 200),
    ]);

    $customer = Customers::get('cust_1');
    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->id())->toBe('cust_1')
        ->and($customer->name())->toBe('Jane Doe');

    $updated = Customers::update('cust_1', ['name' => 'Jane Smith']);
    expect($updated)->toBeInstanceOf(Customer::class)
        ->and($updated->name())->toBe('Jane Smith');
});

it('creates a portal session for a customer', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers/cust_1/portal-sessions' => Http::response([
            'session_id' => 'ps_1',
            'customer_id' => 'cust_1',
            'url' => 'https://portal.bachs.io/session/ps_1',
            'status' => 'active',
            'expires_at' => '2026-01-15T11:00:00Z',
            'created_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);

    $session = Customers::createPortalSession('cust_1');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/customers/cust_1/portal-sessions';
    });

    expect($session)->toBeInstanceOf(PortalSession::class)
        ->and($session->id())->toBe('ps_1')
        ->and($session->customerId())->toBe('cust_1')
        ->and($session->url())->toBe('https://portal.bachs.io/session/ps_1')
        ->and($session->isActive())->toBeTrue();
});

it('passes idempotency key on mutations', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers' => Http::response([
            'customer_id' => 'cust_1',
            'email' => 'jane@example.com',
        ], 201),
    ]);

    Customers::create(['email' => 'jane@example.com'], 'idem_123');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Idempotency-Key')
            && $request->header('Idempotency-Key')[0] === 'idem_123';
    });
});
