<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Dto\PaymentMethod;
use OkekeDev\Bachs\Dto\PaymentRailLookup;
use OkekeDev\Bachs\Resources\PaymentMethods;

it('lists the supported payment methods', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/payment-methods' => Http::response([
            'payment_methods' => [
                [
                    'id' => 'bank_transfer',
                    'display_name' => 'Bank Transfer',
                    'icon' => 'bank',
                    'description' => 'Pay via bank transfer',
                    'type' => 'fiat',
                    'enabled_by_default' => true,
                    'currencies' => ['USD', 'NGN', 'GHS', 'KES', 'ZAR'],
                ],
                [
                    'id' => 'crypto',
                    'display_name' => 'Cryptocurrency',
                    'icon' => 'crypto',
                    'description' => 'Pay with USDT or other cryptocurrencies',
                    'type' => 'crypto',
                    'enabled_by_default' => true,
                    'currencies' => ['USDT_TRC20', 'USDT_ERC20', 'BTC', 'ETH'],
                ],
            ],
        ]),
    ]);

    $methods = PaymentMethods::list();

    Http::assertSent(function ($request) {
        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH) === '/v1/payment-methods';
    });

    expect($methods)->toHaveCount(2)
        ->and($methods[0])->toBeInstanceOf(PaymentMethod::class)
        ->and($methods[0]->id())->toBe('bank_transfer')
        ->and($methods[0]->isFiat())->toBeTrue()
        ->and($methods[0]->supportsCurrency('NGN'))->toBeTrue()
        ->and($methods[1])->toBeInstanceOf(PaymentMethod::class)
        ->and($methods[1]->isCrypto())->toBeTrue()
        ->and($methods[1]->currencies()[0]->code())->toBe('USDT_TRC20');
});

it('lists the payment rails for a method and currency', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/payment-methods/rails?payment_method=bank_transfer&currency=NGN' => Http::response([
            'payment_method' => 'bank_transfer',
            'currency' => 'NGN',
            'country_code' => 'NG',
            'rails' => [
                ['id' => 'bank_transfer_ng', 'name' => 'Bank Transfer Nigeria', 'active' => true],
            ],
        ]),
    ]);

    $lookup = PaymentMethods::rails(['payment_method' => 'bank_transfer', 'currency' => 'NGN']);

    Http::assertSent(function ($request) {
        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH) === '/v1/payment-methods/rails'
            && $request->data() === ['payment_method' => 'bank_transfer', 'currency' => 'NGN'];
    });

    expect($lookup)->toBeInstanceOf(PaymentRailLookup::class)
        ->and($lookup->paymentMethod())->toBe('bank_transfer')
        ->and($lookup->currency()->code())->toBe('NGN')
        ->and($lookup->rails())->toHaveCount(1)
        ->and($lookup->rails()[0]->id())->toBe('bank_transfer_ng')
        ->and($lookup->rails()[0]->active())->toBeTrue();
});
