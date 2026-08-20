<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/checkout-sessions' => Http::response([
            'checkout_id' => 'chk_test',
            'checkout_url' => 'https://checkout.bachs.io/chk_test',
            'status' => 'OPEN',
            'expires_at' => '2026-01-15T11:00:00Z',
            'created_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);
});

it('renders checkout component with link to checkout URL', function () {
    $html = (string) $this->blade('<x-bachs::checkout product="prod_1" email="test@example.com" />');

    expect($html)->toContain('href="https://checkout.bachs.io/chk_test"')
        ->and($html)->toContain('data-bachs-checkout')
        ->and($html)->toContain('role="button"')
        ->and($html)->toContain('Checkout');
});

it('renders checkout component with custom name', function () {
    $html = (string) $this->blade('<x-bachs::checkout product="prod_1" name="Buy Now" />');

    expect($html)->toContain('Buy Now')
        ->and($html)->toContain('aria-label="Buy Now"');
});

it('renders checkout component with slot content', function () {
    $html = (string) $this->blade('<x-bachs::checkout product="prod_1" email="test@example.com">Pay $29.99</x-bachs::checkout>');

    expect($html)->toContain('Pay $29.99');
});

it('renders checkout overlay component with dialog', function () {
    $html = (string) $this->blade('<x-bachs::checkout-overlay product="prod_1" email="test@example.com" />');

    expect($html)->toContain('role="dialog"')
        ->and($html)->toContain('aria-modal="true"')
        ->and($html)->toContain('iframe')
        ->and($html)->toContain('https://checkout.bachs.io/chk_test')
        ->and($html)->toContain('aria-haspopup="dialog"');
});

it('renders checkout overlay with close button', function () {
    $html = (string) $this->blade('<x-bachs::checkout-overlay product="prod_1" />');

    expect($html)->toContain('aria-label="Close checkout"')
        ->and($html)->toContain('&times;');
});

it('renders subscribe component with link to checkout URL', function () {
    $html = (string) $this->blade('<x-bachs::subscribe product="prod_monthly" email="test@example.com" />');

    expect($html)->toContain('href="https://checkout.bachs.io/chk_test"')
        ->and($html)->toContain('data-bachs-subscribe')
        ->and($html)->toContain('role="button"')
        ->and($html)->toContain('Subscribe');
});

it('renders subscribe component with custom name', function () {
    $html = (string) $this->blade('<x-bachs::subscribe product="prod_monthly" name="Start Subscription" />');

    expect($html)->toContain('Start Subscription')
        ->and($html)->toContain('aria-label="Start Subscription"');
});

it('passes product to checkout session creation', function () {
    (string) $this->blade('<x-bachs::checkout product="prod_1" email="test@example.com" />');

    Http::assertSent(function ($request) {
        return $request['product_cart'][0]['product_id'] === 'prod_1'
            && $request['customer']['email'] === 'test@example.com';
    });
});

it('passes customer id to checkout session creation', function () {
    (string) $this->blade('<x-bachs::checkout product="prod_1" customer="cust_1" />');

    Http::assertSent(function ($request) {
        return $request['customer']['customer_id'] === 'cust_1';
    });
});

it('passes success and cancel urls to checkout session creation', function () {
    (string) $this->blade('<x-bachs::checkout product="prod_1" success-url="https://example.com/success" cancel-url="https://example.com/cancel" />');

    Http::assertSent(function ($request) {
        return $request['success_url'] === 'https://example.com/success'
            && $request['cancel_url'] === 'https://example.com/cancel';
    });
});

it('applies custom class to checkout link', function () {
    $html = (string) $this->blade('<x-bachs::checkout product="prod_1" class="btn btn-primary" />');

    expect($html)->toContain('btn btn-primary')
        ->and($html)->toContain('bachs-checkout');
});

it('has accessible markup on checkout link', function () {
    $html = (string) $this->blade('<x-bachs::checkout product="prod_1" />');

    expect($html)->toContain('role="button"')
        ->and($html)->toContain('aria-label="Checkout"');
});

it('has accessible markup on overlay dialog', function () {
    $html = (string) $this->blade('<x-bachs::checkout-overlay product="prod_1" />');

    expect($html)->toContain('role="dialog"')
        ->and($html)->toContain('aria-modal="true"')
        ->and($html)->toContain('aria-label="Checkout"')
        ->and($html)->toContain('aria-haspopup="dialog"')
        ->and($html)->toContain('aria-controls=');
});
