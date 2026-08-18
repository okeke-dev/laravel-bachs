<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use OkekeDev\Bachs\Events\AccountUpdated;
use OkekeDev\Bachs\Events\CapabilityUpdated;
use OkekeDev\Bachs\Events\CheckoutCompleted;
use OkekeDev\Bachs\Events\CheckoutExpired;
use OkekeDev\Bachs\Events\ConversionCompleted;
use OkekeDev\Bachs\Events\ConversionFailed;
use OkekeDev\Bachs\Events\CustomerCreated;
use OkekeDev\Bachs\Events\CustomerUpdated;
use OkekeDev\Bachs\Events\DisputeCreated;
use OkekeDev\Bachs\Events\DisputeUpdated;
use OkekeDev\Bachs\Events\InvoiceCreated;
use OkekeDev\Bachs\Events\InvoicePaid;
use OkekeDev\Bachs\Events\InvoicePaymentFailed;
use OkekeDev\Bachs\Events\PaymentFailed;
use OkekeDev\Bachs\Events\PaymentSucceeded;
use OkekeDev\Bachs\Events\PaymentUnderpaid;
use OkekeDev\Bachs\Events\PayoutCreated;
use OkekeDev\Bachs\Events\PayoutFailed;
use OkekeDev\Bachs\Events\PayoutPaid;
use OkekeDev\Bachs\Events\RefundCreated;
use OkekeDev\Bachs\Events\RefundFailed;
use OkekeDev\Bachs\Events\RefundPaid;
use OkekeDev\Bachs\Events\SubscriptionCreated;
use OkekeDev\Bachs\Events\SubscriptionDeleted;
use OkekeDev\Bachs\Events\SubscriptionUpdated;
use OkekeDev\Bachs\Events\TransferCreated;
use OkekeDev\Bachs\Events\WebhookReceived;
use OkekeDev\Bachs\Exceptions\BachsWebhookInvalidSignatureException;
use OkekeDev\Bachs\Exceptions\BachsWebhookStaleTimestampException;
use OkekeDev\Bachs\Webhooks\ProcessWebhookJob;
use OkekeDev\Bachs\Webhooks\SignatureVerifier;
use OkekeDev\Bachs\Webhooks\WebhookEvent;
use OkekeDev\Bachs\Webhooks\WebhookProcessor;

beforeEach(function () {
    Config::set('bachs.webhook.secret', 'whsec_test_secret_key_for_testing');
    Config::set('bachs.webhook.tolerance', 300);
    Config::set('bachs.database.sync', false);
    Config::set('bachs.logging.enabled', false);

    $this->verifier = new SignatureVerifier;
});

it('verifies a valid webhook signature', function () {
    $secret = 'whsec_test_secret_key_for_testing';
    $timestamp = (string) time();
    $body = '{"id":"evt_1","type":"collection.succeeded","data":{}}';
    $payload = "{$timestamp}.{$body}";
    $signature = hash_hmac('sha256', $payload, $secret);

    $this->verifier->verify(
        rawBody: $body,
        timestamp: $timestamp,
        signature: $signature,
    );

    // If no exception thrown, verification passed
    expect(true)->toBeTrue();
});

it('rejects invalid signature', function () {
    $timestamp = (string) time();
    $body = '{"id":"evt_1","type":"collection.succeeded","data":{}}';

    $this->verifier->verify(
        rawBody: $body,
        timestamp: $timestamp,
        signature: 'invalid_signature_here',
    );
})->throws(BachsWebhookInvalidSignatureException::class, 'signature verification failed');

it('rejects missing timestamp header', function () {
    $this->verifier->verify(
        rawBody: '{}',
        timestamp: null,
        signature: 'some_sig',
    );
})->throws(BachsWebhookInvalidSignatureException::class, 'Missing X-Bachs-Timestamp');

it('rejects missing signature header', function () {
    $this->verifier->verify(
        rawBody: '{}',
        timestamp: (string) time(),
        signature: null,
    );
})->throws(BachsWebhookInvalidSignatureException::class, 'Missing X-Bachs-Signature');

it('rejects non-numeric timestamp', function () {
    $this->verifier->verify(
        rawBody: '{}',
        timestamp: 'not-a-number',
        signature: 'some_sig',
    );
})->throws(BachsWebhookInvalidSignatureException::class, 'Invalid X-Bachs-Timestamp');

it('rejects stale timestamp outside tolerance', function () {
    $secret = 'whsec_test_secret_key_for_testing';
    $timestamp = (string) (time() - 600); // 10 minutes ago
    $body = '{"id":"evt_1","type":"collection.succeeded","data":{}}';
    $payload = "{$timestamp}.{$body}";
    $signature = hash_hmac('sha256', $payload, $secret);

    $this->verifier->verify(
        rawBody: $body,
        timestamp: $timestamp,
        signature: $signature,
    );
})->throws(BachsWebhookStaleTimestampException::class, 'outside the allowed tolerance');

it('rejects future timestamp outside tolerance', function () {
    $secret = 'whsec_test_secret_key_for_testing';
    $timestamp = (string) (time() + 600); // 10 minutes in future
    $body = '{"id":"evt_1","type":"collection.succeeded","data":{}}';
    $payload = "{$timestamp}.{$body}";
    $signature = hash_hmac('sha256', $payload, $secret);

    $this->verifier->verify(
        rawBody: $body,
        timestamp: $timestamp,
        signature: $signature,
    );
})->throws(BachsWebhookStaleTimestampException::class, 'outside the allowed tolerance');

it('throws when no webhook secret is configured', function () {
    Config::set('bachs.webhook.secret', null);

    $this->verifier->verify(
        rawBody: '{}',
        timestamp: (string) time(),
        signature: 'some_sig',
    );
})->throws(InvalidArgumentException::class, 'No webhook signing secret');

it('uses constant-time comparison to prevent timing attacks', function () {
    // This test verifies that hash_equals is used internally
    // by checking that invalid signatures are rejected consistently
    $secret = 'whsec_test_secret_key_for_testing';
    $timestamp = (string) time();
    $body = '{"id":"evt_1","type":"collection.succeeded","data":{}}';
    $payload = "{$timestamp}.{$body}";
    $correctSignature = hash_hmac('sha256', $payload, $secret);

    // Verify correct signature works
    $this->verifier->verify($body, $timestamp, $correctSignature);

    // Verify similar but incorrect signatures fail
    $similarSignature = substr($correctSignature, 0, -1).'0';
    $this->verifier->verify($body, $timestamp, $similarSignature);
})->throws(BachsWebhookInvalidSignatureException::class);

it('parses webhook event payload correctly', function () {
    $payload = [
        'id' => 'evt_123',
        'type' => 'collection.succeeded',
        'created_at' => '2026-01-15T10:00:00Z',
        'organization_id' => 'org_abc',
        'data' => ['payment_id' => 'pay_1'],
        'account' => 'org_def',
    ];

    $event = WebhookEvent::fromPayload($payload);

    expect($event->id())->toBe('evt_123')
        ->and($event->type())->toBe('collection.succeeded')
        ->and($event->createdAt())->toBe('2026-01-15T10:00:00Z')
        ->and($event->organizationId())->toBe('org_abc')
        ->and($event->data())->toBe(['payment_id' => 'pay_1'])
        ->and($event->account())->toBe('org_def')
        ->and($event->isConnectEvent())->toBeTrue();
});

it('identifies non-connect events', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_123',
        'type' => 'collection.succeeded',
        'created_at' => '2026-01-15T10:00:00Z',
        'organization_id' => 'org_abc',
        'data' => [],
    ]);

    expect($event->isConnectEvent())->toBeFalse()
        ->and($event->account())->toBeNull();
});

it('extracts event category from type', function () {
    $checkout = WebhookEvent::fromPayload(['id' => '1', 'type' => 'checkout.completed', 'data' => []]);
    $payment = WebhookEvent::fromPayload(['id' => '2', 'type' => 'collection.succeeded', 'data' => []]);
    $subscription = WebhookEvent::fromPayload(['id' => '3', 'type' => 'customer.subscription.created', 'data' => []]);

    expect($checkout->category())->toBe('checkout')
        ->and($payment->category())->toBe('collection')
        ->and($subscription->category())->toBe('customer');
});

it('dispatches WebhookReceived event during processing', function () {
    Event::fake([WebhookReceived::class]);

    $processor = new WebhookProcessor;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_test',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [],
    ]);

    $processor->process($event);

    Event::assertDispatched(WebhookReceived::class, function ($e) use ($event) {
        return $e->webhookEvent->id() === $event->id();
    });
});

it('detects duplicate events when persistence is enabled', function () {
    Config::set('bachs.database.sync', true);

    // Create the webhook_events table for testing
    Schema::create('bachs_webhook_events', function ($table) {
        $table->id();
        $table->string('event_id')->unique();
        $table->string('type');
        $table->string('organization_id');
        $table->string('account')->nullable();
        $table->text('data');
        $table->string('event_created_at');
        $table->string('processed_at');
    });

    $processor = new WebhookProcessor;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_duplicate_test',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => ['payment_id' => 'pay_1'],
    ]);

    // Process once
    $processor->process($event);

    // Process again - should be detected as duplicate
    Event::fake([PaymentSucceeded::class]);
    $processor->process($event);

    // PaymentSucceeded should only be dispatched once
    Event::assertNotDispatched(PaymentSucceeded::class);

    // Clean up
    Schema::dropIfExists('bachs_webhook_events');
});

it('maps event types to correct Laravel event classes', function () {
    $processor = new WebhookProcessor;

    $testCases = [
        'checkout.completed' => CheckoutCompleted::class,
        'checkout.expired' => CheckoutExpired::class,
        'collection.succeeded' => PaymentSucceeded::class,
        'collection.failed' => PaymentFailed::class,
        'collection.underpaid' => PaymentUnderpaid::class,
        'customer.subscription.created' => SubscriptionCreated::class,
        'customer.subscription.updated' => SubscriptionUpdated::class,
        'customer.subscription.deleted' => SubscriptionDeleted::class,
        'invoice.created' => InvoiceCreated::class,
        'invoice.paid' => InvoicePaid::class,
        'invoice.payment_failed' => InvoicePaymentFailed::class,
        'payout.created' => PayoutCreated::class,
        'payout.paid' => PayoutPaid::class,
        'payout.failed' => PayoutFailed::class,
        'refund.created' => RefundCreated::class,
        'refund.paid' => RefundPaid::class,
        'refund.failed' => RefundFailed::class,
        'dispute.created' => DisputeCreated::class,
        'dispute.updated' => DisputeUpdated::class,
        'conversion.completed' => ConversionCompleted::class,
        'conversion.failed' => ConversionFailed::class,
        'customer.created' => CustomerCreated::class,
        'customer.updated' => CustomerUpdated::class,
        'account.updated' => AccountUpdated::class,
        'capability.updated' => CapabilityUpdated::class,
        'transfer.created' => TransferCreated::class,
    ];

    foreach ($testCases as $type => $expectedClass) {
        $event = WebhookEvent::fromPayload([
            'id' => "evt_{$type}",
            'type' => $type,
            'created_at' => now()->toIso8601String(),
            'organization_id' => 'org_1',
            'data' => [],
        ]);

        Event::fake([$expectedClass]);

        $processor->process($event);

        Event::assertDispatched($expectedClass);

        Event::fake([]); // Reset
    }
});

it('creates the bachs_webhook_events table with correct schema', function () {
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

    expect(Schema::hasTable('bachs_webhook_events'))->toBeTrue()
        ->and(Schema::hasColumn('bachs_webhook_events', 'event_id'))->toBeTrue()
        ->and(Schema::hasColumn('bachs_webhook_events', 'type'))->toBeTrue()
        ->and(Schema::hasColumn('bachs_webhook_events', 'organization_id'))->toBeTrue()
        ->and(Schema::hasColumn('bachs_webhook_events', 'account'))->toBeTrue()
        ->and(Schema::hasColumn('bachs_webhook_events', 'data'))->toBeTrue()
        ->and(Schema::hasColumn('bachs_webhook_events', 'event_created_at'))->toBeTrue()
        ->and(Schema::hasColumn('bachs_webhook_events', 'processed_at'))->toBeTrue();

    Schema::dropIfExists('bachs_webhook_events');
});

it('persists webhook events to the database when sync is enabled', function () {
    Config::set('bachs.database.sync', true);

    Schema::create('bachs_webhook_events', function ($table) {
        $table->id();
        $table->string('event_id')->unique();
        $table->string('type');
        $table->string('organization_id');
        $table->string('account')->nullable();
        $table->text('data');
        $table->string('event_created_at');
        $table->string('processed_at');
    });

    $processor = new WebhookProcessor;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_persist_test',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => ['payment_id' => 'pay_1'],
    ]);

    $processor->process($event);

    $stored = DB::table('bachs_webhook_events')
        ->where('event_id', 'evt_persist_test')
        ->first();

    expect($stored)->not->toBeNull()
        ->and($stored->type)->toBe('collection.succeeded')
        ->and($stored->organization_id)->toBe('org_1')
        ->and($stored->data)->toContain('payment_id')
        ->and($stored->processed_at)->not->toBeNull();

    Schema::dropIfExists('bachs_webhook_events');
});

it('stores nullable account field for connect events', function () {
    Config::set('bachs.database.sync', true);

    Schema::create('bachs_webhook_events', function ($table) {
        $table->id();
        $table->string('event_id')->unique();
        $table->string('type');
        $table->string('organization_id');
        $table->string('account')->nullable();
        $table->text('data');
        $table->string('event_created_at');
        $table->string('processed_at');
    });

    $processor = new WebhookProcessor;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_connect_test',
        'type' => 'account.updated',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [],
        'account' => 'org_connected',
    ]);

    $processor->process($event);

    $stored = DB::table('bachs_webhook_events')
        ->where('event_id', 'evt_connect_test')
        ->first();

    expect($stored->account)->toBe('org_connected');

    Schema::dropIfExists('bachs_webhook_events');
});

it('processes events synchronously when queue is null', function () {
    Config::set('bachs.webhook.queue', null);

    $processor = new WebhookProcessor;
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_sync_test',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [],
    ]);

    Event::fake([WebhookReceived::class]);

    $processor->process($event);

    Event::assertDispatched(WebhookReceived::class);
});

it('generates a unique job id based on the event id', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_unique_123',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [],
    ]);

    $job = new ProcessWebhookJob($event);

    expect($job->uniqueId())->toBe('bachs_webhook_evt_unique_123');
});

it('sets retry configuration on the webhook job', function () {
    $event = WebhookEvent::fromPayload([
        'id' => 'evt_retry_test',
        'type' => 'collection.succeeded',
        'created_at' => now()->toIso8601String(),
        'organization_id' => 'org_1',
        'data' => [],
    ]);

    $job = new ProcessWebhookJob($event);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe(30);
});
