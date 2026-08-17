<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Dto\CheckoutSession;
use OkekeDev\Bachs\Resources\CheckoutSessions;

it('creates a checkout session', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/checkout-sessions' => Http::response([
            'checkout_id' => 'chk_1',
            'checkout_url' => 'https://checkout.bachs.io/chk_1',
            'status' => 'OPEN',
            'expires_at' => '2026-01-15T11:00:00Z',
            'created_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);

    $session = CheckoutSessions::create([
        'customer' => ['customer_id' => 'cust_1'],
        'product_cart' => [['product_id' => 'prod_1', 'quantity' => 1]],
        'success_url' => 'https://example.com/success',
        'cancel_url' => 'https://example.com/cancel',
    ]);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/checkout-sessions'
            && $request['customer']['customer_id'] === 'cust_1'
            && $request['product_cart'][0]['product_id'] === 'prod_1';
    });

    expect($session)->toBeInstanceOf(CheckoutSession::class)
        ->and($session->id())->toBe('chk_1')
        ->and($session->url())->toBe('https://checkout.bachs.io/chk_1')
        ->and($session->status())->toBe('OPEN')
        ->and($session->isOpen())->toBeTrue()
        ->and($session->isComplete())->toBeFalse()
        ->and($session->isExpired())->toBeFalse();
});

it('fetches a checkout session', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/checkout-sessions/chk_1' => Http::response([
            'checkout_id' => 'chk_1',
            'checkout_url' => 'https://checkout.bachs.io/chk_1',
            'status' => 'COMPLETE',
            'payment_status' => 'paid',
            'amount' => '29.00',
            'currency' => 'USD',
            'customer' => ['customer_id' => 'cust_1'],
            'products' => [['product_id' => 'prod_1', 'name' => 'Pro Plan']],
            'session_mode' => 'CART',
            'created_at' => '2026-01-15T10:00:00Z',
        ]),
    ]);

    $session = CheckoutSessions::get('chk_1');

    expect($session)->toBeInstanceOf(CheckoutSession::class)
        ->and($session->id())->toBe('chk_1')
        ->and($session->status())->toBe('COMPLETE')
        ->and($session->isComplete())->toBeTrue()
        ->and($session->paymentStatus())->toBe('paid')
        ->and($session->amount())->toBe('29.00')
        ->and($session->currency())->toBe('USD')
        ->and($session->customerId())->toBe('cust_1')
        ->and($session->products())->toHaveCount(1)
        ->and($session->sessionMode())->toBe('CART')
        ->and($session->isCartMode())->toBeTrue()
        ->and($session->isSelectionMode())->toBeFalse();
});

it('returns a redirect response from checkout session', function () {
    $session = CheckoutSession::from([
        'checkout_id' => 'chk_1',
        'checkout_url' => 'https://checkout.bachs.io/chk_1',
        'status' => 'OPEN',
    ]);

    $response = $session->redirect();

    expect($response->getTargetUrl())->toBe('https://checkout.bachs.io/chk_1');
});

it('passes idempotency key on create', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/checkout-sessions' => Http::response([
            'checkout_id' => 'chk_1',
            'checkout_url' => 'https://checkout.bachs.io/chk_1',
            'status' => 'OPEN',
        ], 201),
    ]);

    CheckoutSessions::create([
        'customer' => ['customer_id' => 'cust_1'],
        'product_cart' => [['product_id' => 'prod_1']],
    ], 'idem_123');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Idempotency-Key')
            && $request->header('Idempotency-Key')[0] === 'idem_123';
    });
});
