<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Resources\Balances;
use OkekeDev\Bachs\Resources\Currencies;

it('lists supported currencies', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/currencies/supported' => Http::response([
            'USD',
            'NGN',
            'KES',
        ]),
    ]);

    $currencies = Currencies::supported();

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/currencies/supported');

    expect($currencies)->toBe(['USD', 'NGN', 'KES']);
});

it('lists payout-supported currencies', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/currencies/payout-supported' => Http::response([
            'NGN',
            'GHS',
        ]),
    ]);

    expect(Currencies::payoutSupported())->toBe(['NGN', 'GHS']);
});

it('fetches the account balances', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/accounts/balances' => Http::response([
            'currency' => 'USD',
            'available' => '1284.50',
            'pending' => '75.00',
        ]),
    ]);

    $balance = Balances::get();

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/accounts/balances');

    expect($balance['currency'])->toBe('USD')
        ->and($balance['available'])->toBe('1284.50');
});
