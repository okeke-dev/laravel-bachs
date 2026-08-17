<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\Subscription;
use OkekeDev\Bachs\Resources\Subscriptions;

it('lists subscriptions as a paginated collection', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/subscriptions*' => Http::response([
            'items' => [
                ['id' => 'sub_1', 'status' => 'active', 'amount' => '29.00', 'currency' => 'USD'],
                ['id' => 'sub_2', 'status' => 'trialing', 'amount' => '49.00', 'currency' => 'USD'],
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

    $subscriptions = Subscriptions::list();

    expect($subscriptions)->toBeInstanceOf(PaginatedCollection::class)
        ->and($subscriptions->count())->toBe(2)
        ->and($subscriptions->first())->toBeInstanceOf(Subscription::class)
        ->and($subscriptions->first()->id())->toBe('sub_1')
        ->and($subscriptions->first()->isActive())->toBeTrue();
});

it('fetches a subscription', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/subscriptions/sub_1' => Http::response([
            'id' => 'sub_1',
            'customer' => ['customer_id' => 'cust_1'],
            'status' => 'active',
            'amount' => '29.00',
            'currency' => 'USD',
            'billing_cycle' => ['interval' => 'month', 'frequency' => 1],
            'current_period_start' => '2026-01-01T00:00:00Z',
            'current_period_end' => '2026-02-01T00:00:00Z',
            'product' => ['id' => 'prod_1', 'name' => 'Pro Plan'],
            'created_at' => '2026-01-01T00:00:00Z',
        ]),
    ]);

    $subscription = Subscriptions::get('sub_1');

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->id())->toBe('sub_1')
        ->and($subscription->customerId())->toBe('cust_1')
        ->and($subscription->status())->toBe('active')
        ->and($subscription->isActive())->toBeTrue()
        ->and($subscription->amount())->toBe('29.00')
        ->and($subscription->currency())->toBe('USD')
        ->and($subscription->billingCycle())->toBe(['interval' => 'month', 'frequency' => 1])
        ->and($subscription->productId())->toBe('prod_1');
});

it('updates a subscription', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/subscriptions/sub_1' => Http::response([
            'id' => 'sub_1',
            'status' => 'active',
            'product' => ['id' => 'prod_2', 'name' => 'Enterprise Plan'],
            'amount' => '99.00',
            'currency' => 'USD',
        ]),
    ]);

    $subscription = Subscriptions::update('sub_1', ['product_id' => 'prod_2']);

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->productId())->toBe('prod_2')
        ->and($subscription->amount())->toBe('99.00');

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/subscriptions/sub_1'
            && $request['product_id'] === 'prod_2';
    });
});

it('cancels a subscription', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/subscriptions/sub_1' => Http::response([
            'id' => 'sub_1',
            'status' => 'canceled',
            'canceled_at' => '2026-01-15T10:00:00Z',
        ]),
    ]);

    $subscription = Subscriptions::cancel('sub_1');

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->isCanceled())->toBeTrue();

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/subscriptions/sub_1');
});

it('identifies subscription statuses correctly', function () {
    $active = Subscription::from(['id' => 'sub_1', 'status' => 'active']);
    $trialing = Subscription::from(['id' => 'sub_2', 'status' => 'trialing']);
    $pastDue = Subscription::from(['id' => 'sub_3', 'status' => 'past_due']);
    $canceled = Subscription::from(['id' => 'sub_4', 'status' => 'canceled']);
    $paused = Subscription::from(['id' => 'sub_5', 'status' => 'paused']);

    expect($active->isActive())->toBeTrue()
        ->and($active->isTrialing())->toBeFalse()
        ->and($trialing->isTrialing())->toBeTrue()
        ->and($trialing->isActive())->toBeFalse()
        ->and($pastDue->isPastDue())->toBeTrue()
        ->and($canceled->isCanceled())->toBeTrue()
        ->and($paused->isPaused())->toBeTrue();
});

it('handles cancel_at_period_end flag', function () {
    $subscription = Subscription::from([
        'id' => 'sub_1',
        'status' => 'active',
        'cancel_at_period_end' => true,
    ]);

    expect($subscription->cancelAtPeriodEnd())->toBeTrue();
});

it('handles subscription items and metadata', function () {
    $subscription = Subscription::from([
        'id' => 'sub_1',
        'status' => 'active',
        'items' => [
            ['product_id' => 'prod_1', 'quantity' => 2],
        ],
        'metadata' => ['key' => 'value'],
    ]);

    expect($subscription->items())->toHaveCount(1)
        ->and($subscription->items()[0]['product_id'])->toBe('prod_1')
        ->and($subscription->metadata())->toBe(['key' => 'value']);
});
