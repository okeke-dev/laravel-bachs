<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Dto\SupportedCurrencies;
use OkekeDev\Bachs\Resources\Balances;
use OkekeDev\Bachs\Resources\Currencies;

it('lists supported currencies grouped by type', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/currencies/supported' => Http::response([
            'fiat' => ['USD', 'NGN', 'GHS', 'KES'],
            'crypto' => ['USDT_TRC20', 'BTC'],
        ]),
    ]);

    $currencies = Currencies::supported();

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/currencies/supported');

    expect($currencies)->toBeInstanceOf(SupportedCurrencies::class)
        ->and($currencies->fiat())->toHaveCount(4)
        ->and($currencies->crypto())->toHaveCount(2)
        ->and($currencies->supports('NGN'))->toBeTrue();
});

it('lists payout-supported currencies', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/currencies/payout-supported' => Http::response([
            'fiat' => ['NGN', 'GHS'],
            'crypto' => [],
        ]),
    ]);

    $currencies = Currencies::payoutSupported();

    expect($currencies)->toBeInstanceOf(SupportedCurrencies::class)
        ->and($currencies->fiat())->toHaveCount(2)
        ->and($currencies->crypto())->toBe([])
        ->and($currencies->supports('GHS'))->toBeTrue()
        ->and($currencies->supports('USD'))->toBeFalse();
});

it('fetches the account balances', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/accounts/balances' => Http::response([
            'account_id' => 'org_1',
            'balances' => [
                ['currency' => 'USD', 'available_balance' => '1284.50', 'pending_balance' => '75.00'],
            ],
            'total_balance_usd' => '1359.50',
            'pending_settlements_by_day' => [],
        ]),
    ]);

    $balance = Balances::get();

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/accounts/balances');

    expect($balance->accountId())->toBe('org_1')
        ->and($balance->balances())->toHaveCount(1)
        ->and($balance->balances()[0]->currency()->code())->toBe('USD')
        ->and($balance->balances()[0]->availableBalance()->amount())->toBe('1284.50')
        ->and($balance->totalBalanceUsd()->amount())->toBe('1359.50');
});
