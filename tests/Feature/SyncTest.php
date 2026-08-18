<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OkekeDev\Bachs\Models\BachsCustomer;
use OkekeDev\Bachs\Models\BachsPayment;
use OkekeDev\Bachs\Models\BachsProduct;
use OkekeDev\Bachs\Models\BachsSubscription;
use OkekeDev\Bachs\Webhooks\WebhookEvent;
use OkekeDev\Bachs\Webhooks\WebhookProcessor;
use OkekeDev\Bachs\Webhooks\WebhookSyncer;

beforeEach(function () {
    Config::set('bachs.database.sync', true);
    Config::set('bachs.logging.enabled', false);

    // Create webhook events table (needed by WebhookProcessor duplicate check)
    Schema::create('bachs_webhook_events', function ($table) {
        $table->id();
        $table->string('event_id')->unique();
        $table->string('type');
        $table->string('organization_id');
        $table->string('account')->nullable();
        $table->text('data');
        $table->string('event_created_at');
        $table->string('processed_at');
        $table->timestamps();
    });

    // Create all tables for testing
    Schema::create('bachs_customers', function ($table) {
        $table->id();
        $table->string('bachs_id')->unique();
        $table->string('email');
        $table->string('name')->nullable();
        $table->string('phone_number')->nullable();
        $table->json('metadata')->nullable();
        $table->json('billing_address')->nullable();
        $table->string('bachs_created_at')->nullable();
        $table->string('bachs_updated_at')->nullable();
        $table->timestamps();
    });

    Schema::create('bachs_products', function ($table) {
        $table->id();
        $table->string('bachs_id')->unique();
        $table->string('organization_id');
        $table->string('name');
        $table->string('description')->nullable();
        $table->string('status')->default('active');
        $table->json('metadata')->nullable();
        $table->json('billing_cycle')->nullable();
        $table->json('trial_period')->nullable();
        $table->string('bachs_created_at')->nullable();
        $table->string('bachs_updated_at')->nullable();
        $table->string('bachs_archived_at')->nullable();
        $table->timestamps();
    });

    Schema::create('bachs_payments', function ($table) {
        $table->id();
        $table->string('bachs_id')->unique();
        $table->string('charge_id')->nullable()->unique();
        $table->string('reference')->nullable();
        $table->string('billing_reason')->nullable();
        $table->string('checkout_id')->nullable();
        $table->string('status');
        $table->string('amount')->nullable();
        $table->string('amount_paid')->nullable();
        $table->string('amount_remaining')->nullable();
        $table->string('currency')->nullable();
        $table->string('fee_usd')->nullable();
        $table->string('payment_method')->nullable();
        $table->string('subscription_id')->nullable();
        $table->json('line_items')->nullable();
        $table->json('refunds')->nullable();
        $table->json('status_history')->nullable();
        $table->json('metadata')->nullable();
        $table->string('bachs_created_at')->nullable();
        $table->string('bachs_updated_at')->nullable();
        $table->timestamps();
    });

    Schema::create('bachs_subscriptions', function ($table) {
        $table->id();
        $table->string('bachs_id')->unique();
        $table->string('customer_id')->nullable();
        $table->string('payment_method_id')->nullable();
        $table->string('status');
        $table->string('collection_method')->nullable();
        $table->string('currency')->nullable();
        $table->string('amount')->nullable();
        $table->integer('quantity')->default(1);
        $table->json('billing_cycle')->nullable();
        $table->string('current_period_start')->nullable();
        $table->string('current_period_end')->nullable();
        $table->string('next_billed_at')->nullable();
        $table->string('trial_end')->nullable();
        $table->boolean('cancel_at_period_end')->default(false);
        $table->string('canceled_at')->nullable();
        $table->string('product_id')->nullable();
        $table->json('items')->nullable();
        $table->json('metadata')->nullable();
        $table->string('bachs_created_at')->nullable();
        $table->string('bachs_updated_at')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('bachs_webhook_events');
    Schema::dropIfExists('bachs_customers');
    Schema::dropIfExists('bachs_products');
    Schema::dropIfExists('bachs_payments');
    Schema::dropIfExists('bachs_subscriptions');
});

it('syncs a customer from a customer.created event', function () {
    $syncer = new WebhookSyncer;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_1',
        'type' => 'customer.created',
        'created_at' => '2026-01-15T10:00:00Z',
        'organization_id' => 'org_1',
        'data' => [
            'customer_id' => 'cus_123',
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'phone_number' => '+1234567890',
            'metadata' => ['key' => 'value'],
        ],
    ]);

    $syncer->sync($event);

    $customer = DB::table('bachs_customers')->where('bachs_id', 'cus_123')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->email)->toBe('test@example.com')
        ->and($customer->name)->toBe('John Doe')
        ->and($customer->phone_number)->toBe('+1234567890')
        ->and($customer->metadata)->toContain('key');
});

it('upserts customer on duplicate bachs_id', function () {
    DB::table('bachs_customers')->insert([
        'bachs_id' => 'cus_456',
        'email' => 'old@example.com',
        'name' => 'Old Name',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $syncer = new WebhookSyncer;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_2',
        'type' => 'customer.updated',
        'created_at' => '2026-01-15T10:00:00Z',
        'organization_id' => 'org_1',
        'data' => [
            'customer_id' => 'cus_456',
            'email' => 'new@example.com',
            'name' => 'New Name',
        ],
    ]);

    $syncer->sync($event);

    $customer = DB::table('bachs_customers')->where('bachs_id', 'cus_456')->first();

    expect($customer->email)->toBe('new@example.com')
        ->and($customer->name)->toBe('New Name')
        ->and(DB::table('bachs_customers')->where('bachs_id', 'cus_456')->count())->toBe(1);
});

it('syncs a product from a checkout.completed event', function () {
    $syncer = new WebhookSyncer;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_3',
        'type' => 'checkout.completed',
        'created_at' => '2026-01-15T10:00:00Z',
        'organization_id' => 'org_1',
        'data' => [
            'product' => [
                'id' => 'prod_789',
                'organization_id' => 'org_1',
                'name' => 'Premium Plan',
                'status' => 'active',
                'billing_cycle' => ['interval' => 'month', 'frequency' => 1],
            ],
        ],
    ]);

    $syncer->sync($event);

    $product = DB::table('bachs_products')->where('bachs_id', 'prod_789')->first();

    expect($product)->not->toBeNull()
        ->and($product->name)->toBe('Premium Plan')
        ->and($product->status)->toBe('active')
        ->and($product->billing_cycle)->toContain('month');
});

it('syncs a payment from a collection.succeeded event', function () {
    $syncer = new WebhookSyncer;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_4',
        'type' => 'collection.succeeded',
        'created_at' => '2026-01-15T10:00:00Z',
        'organization_id' => 'org_1',
        'data' => [
            'payment_id' => 'pay_abc',
            'charge_id' => 'ch_xyz',
            'status' => 'succeeded',
            'amount' => '29.99',
            'currency' => 'USD',
            'subscription_id' => 'sub_123',
        ],
    ]);

    $syncer->sync($event);

    $payment = DB::table('bachs_payments')->where('bachs_id', 'pay_abc')->first();

    expect($payment)->not->toBeNull()
        ->and($payment->charge_id)->toBe('ch_xyz')
        ->and($payment->status)->toBe('succeeded')
        ->and($payment->amount)->toBe('29.99')
        ->and($payment->currency)->toBe('USD')
        ->and($payment->subscription_id)->toBe('sub_123');
});

it('syncs a subscription from a customer.subscription.created event', function () {
    $syncer = new WebhookSyncer;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_5',
        'type' => 'customer.subscription.created',
        'created_at' => '2026-01-15T10:00:00Z',
        'organization_id' => 'org_1',
        'data' => [
            'subscription_id' => 'sub_abc',
            'customer_id' => 'cus_123',
            'status' => 'active',
            'amount' => '29.99',
            'currency' => 'USD',
            'product_id' => 'prod_789',
            'cancel_at_period_end' => false,
        ],
    ]);

    $syncer->sync($event);

    $subscription = DB::table('bachs_subscriptions')->where('bachs_id', 'sub_abc')->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->customer_id)->toBe('cus_123')
        ->and($subscription->status)->toBe('active')
        ->and($subscription->amount)->toBe('29.99')
        ->and($subscription->product_id)->toBe('prod_789')
        ->and((bool) $subscription->cancel_at_period_end)->toBeFalse();
});

it('syncs a subscription from a customer.subscription.updated event', function () {
    DB::table('bachs_subscriptions')->insert([
        'bachs_id' => 'sub_old',
        'customer_id' => 'cus_123',
        'status' => 'active',
        'quantity' => 1,
        'cancel_at_period_end' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $syncer = new WebhookSyncer;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_6',
        'type' => 'customer.subscription.updated',
        'created_at' => '2026-01-15T10:00:00Z',
        'organization_id' => 'org_1',
        'data' => [
            'subscription_id' => 'sub_old',
            'status' => 'past_due',
            'cancel_at_period_end' => true,
        ],
    ]);

    $syncer->sync($event);

    $subscription = DB::table('bachs_subscriptions')->where('bachs_id', 'sub_old')->first();

    expect($subscription->status)->toBe('past_due')
        ->and((bool) $subscription->cancel_at_period_end)->toBeTrue()
        ->and(DB::table('bachs_subscriptions')->where('bachs_id', 'sub_old')->count())->toBe(1);
});

it('skips sync when customer_id is missing', function () {
    $syncer = new WebhookSyncer;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_7',
        'type' => 'customer.created',
        'created_at' => '2026-01-15T10:00:00Z',
        'organization_id' => 'org_1',
        'data' => [
            'email' => 'test@example.com',
        ],
    ]);

    $syncer->sync($event);

    expect(DB::table('bachs_customers')->count())->toBe(0);
});

it('syncs customer from processor when sync is enabled', function () {
    $processor = new WebhookProcessor;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_processor_test',
        'type' => 'customer.created',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'customer_id' => 'cus_processor',
            'email' => 'processor@example.com',
            'name' => 'Processor Test',
        ],
    ]);

    $processor->process($event);

    $customer = DB::table('bachs_customers')->where('bachs_id', 'cus_processor')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->email)->toBe('processor@example.com');
});

it('does not sync when database sync is disabled', function () {
    Config::set('bachs.database.sync', false);

    $processor = new WebhookProcessor;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_no_sync',
        'type' => 'customer.created',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [
            'customer_id' => 'cus_nosync',
            'email' => 'nosync@example.com',
        ],
    ]);

    $processor->process($event);

    expect(DB::table('bachs_customers')->count())->toBe(0);
});

it('provides model classes via config', function () {
    expect(config('bachs.database.models.customer'))->toBe(BachsCustomer::class)
        ->and(config('bachs.database.models.product'))->toBe(BachsProduct::class)
        ->and(config('bachs.database.models.payment'))->toBe(BachsPayment::class)
        ->and(config('bachs.database.models.subscription'))->toBe(BachsSubscription::class);
});

it('resolves models from the container', function () {
    expect(app('bachs.customer'))->toBeInstanceOf(BachsCustomer::class)
        ->and(app('bachs.product'))->toBeInstanceOf(BachsProduct::class)
        ->and(app('bachs.payment'))->toBeInstanceOf(BachsPayment::class)
        ->and(app('bachs.subscription'))->toBeInstanceOf(BachsSubscription::class);
});

it('has correct table accessors on models', function () {
    Config::set('bachs.database.tables.customers', 'custom_customers');
    Config::set('bachs.database.tables.products', 'custom_products');
    Config::set('bachs.database.tables.payments', 'custom_payments');
    Config::set('bachs.database.tables.subscriptions', 'custom_subscriptions');

    expect((new BachsCustomer)->getTable())->toBe('custom_customers')
        ->and((new BachsProduct)->getTable())->toBe('custom_products')
        ->and((new BachsPayment)->getTable())->toBe('custom_payments')
        ->and((new BachsSubscription)->getTable())->toBe('custom_subscriptions');
});

it('casts json columns correctly on models', function () {
    $customer = new BachsCustomer;
    $customer->metadata = ['key' => 'value'];
    $customer->billing_address = ['line1' => '123 Main St'];

    expect($customer->metadata)->toBeArray()
        ->and($customer->billing_address)->toBeArray();

    $product = new BachsProduct;
    $product->billing_cycle = ['interval' => 'month', 'frequency' => 1];

    expect($product->billing_cycle)->toBeArray();

    $subscription = new BachsSubscription;
    $subscription->cancel_at_period_end = true;
    $subscription->quantity = 5;

    expect($subscription->cancel_at_period_end)->toBeTrue()
        ->and($subscription->quantity)->toBeInt();
});
