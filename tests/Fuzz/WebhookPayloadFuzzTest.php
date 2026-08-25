<?php

use Illuminate\Support\Facades\Config;
use OkekeDev\Bachs\Webhooks\WebhookEvent;
use OkekeDev\Bachs\Webhooks\WebhookProcessor;

beforeEach(function () {
    Config::set('bachs.database.sync', false);
    Config::set('bachs.logging.enabled', false);
});

it('handles webhook event with empty data payload', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_empty_data',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [],
    ]);

    expect($event->id())->toBe('evt_empty_data')
        ->and($event->data())->toBe([]);
});

it('handles webhook event with deeply nested data payload', function () {
    $deepData = [
        'level1' => [
            'level2' => [
                'level3' => [
                    'level4' => [
                        'level5' => [
                            'value' => 'deep',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $event = WebhookEvent::fromPayload([
        'id' => 'evt_deep',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => $deepData,
    ]);

    expect($event->data())->toBe($deepData)
        ->and($event->data()['level1']['level2']['level3']['level4']['level5']['value'])->toBe('deep');
});

it('handles webhook event with unicode characters in data', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_unicode',
        'type' => 'customer.created',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'customer_id' => 'cust_1',
            'name' => '日本テスト太郎 🎉',
            'email' => 'test@example.com',
            'metadata' => ['note' => 'Café résumé naïve'],
        ],
    ]);

    expect($event->data()['name'])->toBe('日本テスト太郎 🎉')
        ->and($event->data()['metadata']['note'])->toBe('Café résumé naïve');
});

it('handles webhook event with very long string values', function () {
    $longString = str_repeat('a', 10000);

    $event = WebhookEvent::fromPayload([
        'id' => 'evt_long',
        'type' => 'customer.created',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'customer_id' => 'cust_1',
            'name' => $longString,
        ],
    ]);

    expect($event->data()['name'])->toBe($longString)
        ->and(strlen($event->data()['name']))->toBe(10000);
});

it('handles webhook event with numeric string values', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_numeric',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'payment_id' => 'pay_1',
            'amount' => '29.99',
            'currency' => 'usd',
        ],
    ]);

    expect($event->data()['amount'])->toBe('29.99');
});

it('handles webhook event with null optional fields', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_nulls',
        'type' => 'customer.subscription.created',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'subscription_id' => 'sub_1',
            'customer_id' => null,
            'payment_method_id' => null,
            'trial_end' => null,
            'canceled_at' => null,
        ],
    ]);

    expect($event->data()['customer_id'])->toBeNull()
        ->and($event->data()['payment_method_id'])->toBeNull();
});

it('handles webhook event with boolean values in data', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_bool',
        'type' => 'customer.subscription.updated',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'subscription_id' => 'sub_1',
            'cancel_at_period_end' => true,
            'is_gifted' => false,
        ],
    ]);

    expect($event->data()['cancel_at_period_end'])->toBeTrue()
        ->and($event->data()['is_gifted'])->toBeFalse();
});

it('handles webhook event with array values in data', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_arrays',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'payment_id' => 'pay_1',
            'line_items' => [
                ['description' => 'Item 1', 'amount' => '10.00'],
                ['description' => 'Item 2', 'amount' => '20.00'],
            ],
            'metadata' => ['tags' => ['pro', 'annual']],
        ],
    ]);

    expect($event->data()['line_items'])->toHaveCount(2)
        ->and($event->data()['metadata']['tags'])->toBe(['pro', 'annual']);
});

it('handles webhook event with special characters in IDs', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_special:id-with.dots_and-dashes',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'payment_id' => 'pay_123-456_789',
        ],
    ]);

    expect($event->id())->toBe('evt_special:id-with.dots_and-dashes');
});

it('handles webhook processor with minimal event data', function () {
    $processor = new WebhookProcessor;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_minimal',
        'type' => 'account.updated',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [],
    ]);

    $processor->process($event);

    expect(true)->toBeTrue();
});

it('handles webhook processor with large list of events', function () {
    $processor = new WebhookProcessor;

    for ($i = 0; $i < 50; $i++) {
        $event = WebhookEvent::fromPayload([
            'id' => "evt_batch_{$i}",
            'type' => 'account.updated',
            'created_at' => now()->toIso8601String(),
            'organization_id' => 'org_1',
            'data' => ['index' => $i],
        ]);

        $processor->process($event);
    }

    expect(true)->toBeTrue();
});

it('handles webhook event with empty string values', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_empty_strings',
        'type' => 'customer.created',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'customer_id' => 'cust_1',
            'email' => '',
            'name' => '',
            'phone_number' => '',
        ],
    ]);

    expect($event->data()['email'])->toBe('')
        ->and($event->data()['name'])->toBe('');
});

it('handles webhook event with zero values', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_zeros',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'payment_id' => 'pay_1',
            'amount' => '0.00',
            'quantity' => 0,
        ],
    ]);

    expect($event->data()['amount'])->toBe('0.00')
        ->and($event->data()['quantity'])->toBe(0);
});

it('handles event with connect account field', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_connect',
        'type' => 'customer.created',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => ['customer_id' => 'cust_1'],
        'account' => 'org_connected_123',
    ]);

    expect($event->isConnectEvent())->toBeTrue()
        ->and($event->account())->toBe('org_connected_123')
        ->and($event->category())->toBe('customer');
});

it('handles event with missing optional created_at', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_no_created',
        'type' => 'collection.succeeded',
        'organization_id' => 'org_1',
        'data' => [],
    ]);

    expect($event->id())->toBe('evt_no_created')
        ->and($event->createdAt())->not->toBe('evt_no_created');
});
