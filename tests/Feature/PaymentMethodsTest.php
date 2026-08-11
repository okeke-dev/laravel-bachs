<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Resources\PaymentMethods;

it('lists payment methods as a paginated collection', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/payment-methods*' => Http::response([
            'items' => [
                ['id' => 'pm_1', 'brand' => 'visa', 'last4' => '4242'],
            ],
            'pagination' => [
                'has_more' => false,
                'next_cursor' => null,
                'prev_cursor' => null,
                'limit' => 20,
                'offset' => 0,
                'returned' => 1,
                'total' => 1,
            ],
        ]),
    ]);

    $methods = PaymentMethods::list(['limit' => 20]);

    Http::assertSent(function ($request) {
        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH) === '/v1/payment-methods'
            && $request->data() === ['limit' => 20];
    });

    expect($methods)->toBeInstanceOf(PaginatedCollection::class)
        ->and($methods->count())->toBe(1)
        ->and($methods->first()['brand'])->toBe('visa')
        ->and($methods->total())->toBe(1);
});

it('lists the payment rails', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/payment-methods/rails' => Http::response([
            ['id' => 'card', 'name' => 'Card'],
            ['id' => 'mobile_money', 'name' => 'Mobile money'],
        ]),
    ]);

    $rails = PaymentMethods::rails();

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/payment-methods/rails');

    expect($rails)->toBe([
        ['id' => 'card', 'name' => 'Card'],
        ['id' => 'mobile_money', 'name' => 'Mobile money'],
    ]);
});
