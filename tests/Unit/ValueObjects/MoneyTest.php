<?php

use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\ValueObjects\Currency;
use OkekeDev\Bachs\ValueObjects\Money;

it('builds from a decimal string and keeps it verbatim', function () {
    $money = Money::fromDecimal('29.00', 'USD');

    expect($money->amount())->toBe('29.00')
        ->and($money->currency()->code())->toBe('USD')
        ->and((string) $money)->toBe('29.00');
});

it('rejects floats at the boundary', function () {
    Money::fromDecimal(29.00, 'USD');
})->throws(BachsInvalidArgumentException::class, 'never floats');

it('rejects malformed decimal strings', function () {
    Money::fromDecimal('abc', 'USD');
})->throws(BachsInvalidArgumentException::class);

it('accepts integer input', function () {
    expect(Money::fromDecimal(29, 'USD')->amount())->toBe('29');
});

it('compares amounts exactly across scales', function () {
    expect(Money::fromDecimal('29.00', 'USD')->equals(Money::fromDecimal('29', 'USD')))->toBeTrue()
        ->and(Money::fromDecimal('29.00', 'USD')->equals(Money::fromDecimal('29.01', 'USD')))->toBeFalse()
        ->and(Money::fromDecimal('29.00', 'USD')->equals(Money::fromDecimal('29.00', 'NGN')))->toBeFalse();
});

it('detects zero and negative amounts', function () {
    expect(Money::fromDecimal('0.00', 'USD')->isZero())->toBeTrue()
        ->and(Money::fromDecimal('1.00', 'USD')->isZero())->toBeFalse()
        ->and(Money::fromDecimal('-1.00', 'USD')->isNegative())->toBeTrue();
});

it('adds and subtracts without rounding beyond input precision', function () {
    $a = Money::fromDecimal('29.00', 'USD');
    $b = Money::fromDecimal('1.50', 'USD');

    expect($a->add($b)->amount())->toBe('30.50');
    expect($a->subtract($b)->amount())->toBe('27.50');
    expect(Money::fromDecimal('1.001', 'USD')->add('2', 'USD')->amount())->toBe('3.001');
});

it('accepts scalars in arithmetic', function () {
    $money = Money::fromDecimal('10.00', 'USD');

    expect($money->add('5')->amount())->toBe('15.00')
        ->and($money->add(5)->amount())->toBe('15.00')
        ->and($money->subtract('3.50')->amount())->toBe('6.50');
});

it('rejects combining different currencies', function () {
    Money::fromDecimal('1.00', 'USD')->add(Money::fromDecimal('1.00', 'NGN'));
})->throws(BachsInvalidArgumentException::class, 'currencies differ');

it('keeps arithmetic immutable', function () {
    $money = Money::fromDecimal('10.00', 'USD');

    expect($money->add('5')->amount())->toBe('15.00')
        ->and($money->amount())->toBe('10.00');
});

it('formats fiat with a locale-aware symbol', function () {
    expect(Money::fromDecimal('29.00', 'USD')->format())->toBe('$29.00');
});

it('formats crypto without a symbol', function () {
    expect(Money::fromDecimal('6.50', 'USDT_TRC20')->format())->toBe('6.50 USDT_TRC20');
});

it('accepts a Currency value object', function () {
    expect(Money::fromDecimal('5.00', Currency::fromCode('NGN'))->currency()->code())->toBe('NGN');
});
