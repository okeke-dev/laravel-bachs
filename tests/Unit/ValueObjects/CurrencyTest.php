<?php

use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\ValueObjects\Currency;

it('normalizes currency codes to uppercase', function () {
    expect(Currency::fromCode('usd')->code())->toBe('USD');
});

it('accepts crypto codes with network suffixes', function () {
    expect(Currency::fromCode('USDT_TRC20')->code())->toBe('USDT_TRC20');
});

it('rejects malformed currency codes', function (string $code) {
    Currency::fromCode($code);
})->with(['', 'A1', 'us', 'U$D', 'USDT_TRC20_EXTRA_NET'])->throws(BachsInvalidArgumentException::class);

it('returns null from tryFromCode for malformed codes', function () {
    expect(Currency::tryFromCode('not a code'))->toBeNull();
});

it('distinguishes fiat and crypto', function () {
    expect(Currency::fromCode('USD')->isFiat())->toBeTrue()
        ->and(Currency::fromCode('USD')->isCrypto())->toBeFalse()
        ->and(Currency::fromCode('USDT_TRC20')->isCrypto())->toBeTrue()
        ->and(Currency::fromCode('BTC')->isCrypto())->toBeTrue();
});

it('exposes decimal places for the supported set', function () {
    expect(Currency::fromCode('USD')->decimals())->toBe(2)
        ->and(Currency::fromCode('JPY')->decimals())->toBe(0)
        ->and(Currency::fromCode('BHD')->decimals())->toBe(3)
        ->and(Currency::fromCode('USDT')->decimals())->toBe(6)
        ->and(Currency::fromCode('BTC')->decimals())->toBe(8)
        ->and(Currency::fromCode('XYZ')->decimals())->toBe(2);
});

it('compares currencies by code', function () {
    expect(Currency::fromCode('ngn')->equals(Currency::fromCode('NGN')))->toBeTrue()
        ->and(Currency::fromCode('NGN')->equals(Currency::fromCode('GHS')))->toBeFalse();
});

it('casts to its code', function () {
    expect((string) Currency::fromCode('USD'))->toBe('USD');
});
