<?php

namespace OkekeDev\Bachs\Webhooks;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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
use OkekeDev\Bachs\Events\WebhookEventDispatched;
use OkekeDev\Bachs\Events\WebhookReceived;

/**
 * Processes verified webhook events.
 *
 * Identifies the event type, persists it (if configured), detects duplicates,
 * and dispatches the appropriate Laravel event.
 */
class WebhookProcessor
{
    /**
     * Process a verified webhook event.
     */
    public function process(WebhookEvent $event): void
    {
        // Log the event receipt (safe metadata only)
        $this->logEvent($event);

        // Dispatch the generic WebhookReceived event
        Event::dispatch(new WebhookReceived($event));

        // Check for duplicate event
        if ($this->isDuplicate($event)) {
            Log::info('Duplicate webhook event received, skipping.', [
                'event_id' => $event->id(),
                'event_type' => $event->type(),
            ]);

            return;
        }

        // Persist the event if database sync is enabled
        if ($this->shouldPersist()) {
            $this->persistEvent($event);
        }

        // Dispatch the typed Laravel event
        $this->dispatchTypedEvent($event);

        // Log the event dispatch
        Event::dispatch(new WebhookEventDispatched($event));
    }

    /**
     * Determine if this event has already been processed.
     */
    protected function isDuplicate(WebhookEvent $event): bool
    {
        if (! $this->shouldPersist()) {
            return false;
        }

        $table = Config::get('bachs.database.tables.webhook_events', 'bachs_webhook_events');

        return DB::table($table)
            ->where('event_id', $event->id())
            ->exists();
    }

    /**
     * Persist the event to the database.
     */
    protected function persistEvent(WebhookEvent $event): void
    {
        $table = Config::get('bachs.database.tables.webhook_events', 'bachs_webhook_events');
        $connection = Config::get('bachs.database.connection');

        DB::connection($connection)
            ->table($table)
            ->insert([
                'event_id' => $event->id(),
                'type' => $event->type(),
                'organization_id' => $event->organizationId(),
                'account' => $event->account(),
                'data' => json_encode($event->data()),
                'created_at' => $event->createdAt(),
                'processed_at' => now()->toIso8601String(),
            ]);
    }

    /**
     * Dispatch the typed Laravel event based on the Bachs event type.
     */
    protected function dispatchTypedEvent(WebhookEvent $event): void
    {
        $eventClass = $this->resolveEventClass($event->type());

        if ($eventClass !== null) {
            Event::dispatch(new $eventClass($event));
        } else {
            Log::warning('Unknown webhook event type received.', [
                'event_type' => $event->type(),
                'event_id' => $event->id(),
            ]);
        }
    }

    /**
     * Resolve the Laravel event class for a Bachs event type.
     */
    protected function resolveEventClass(string $type): ?string
    {
        $mapping = [
            // Checkout events
            'checkout.completed' => CheckoutCompleted::class,
            'checkout.expired' => CheckoutExpired::class,

            // Payment events
            'collection.succeeded' => PaymentSucceeded::class,
            'collection.failed' => PaymentFailed::class,
            'collection.underpaid' => PaymentUnderpaid::class,

            // Subscription events
            'customer.subscription.created' => SubscriptionCreated::class,
            'customer.subscription.updated' => SubscriptionUpdated::class,
            'customer.subscription.deleted' => SubscriptionDeleted::class,

            // Invoice events
            'invoice.created' => InvoiceCreated::class,
            'invoice.paid' => InvoicePaid::class,
            'invoice.payment_failed' => InvoicePaymentFailed::class,

            // Payout events
            'payout.created' => PayoutCreated::class,
            'payout.paid' => PayoutPaid::class,
            'payout.failed' => PayoutFailed::class,

            // Refund events
            'refund.created' => RefundCreated::class,
            'refund.paid' => RefundPaid::class,
            'refund.failed' => RefundFailed::class,

            // Dispute events
            'dispute.created' => DisputeCreated::class,
            'dispute.updated' => DisputeUpdated::class,

            // Conversion events
            'conversion.completed' => ConversionCompleted::class,
            'conversion.failed' => ConversionFailed::class,

            // Customer events
            'customer.created' => CustomerCreated::class,
            'customer.updated' => CustomerUpdated::class,

            // Connect events
            'account.updated' => AccountUpdated::class,
            'capability.updated' => CapabilityUpdated::class,
            'transfer.created' => TransferCreated::class,
        ];

        return $mapping[$type] ?? null;
    }

    /**
     * Determine if events should be persisted to the database.
     */
    protected function shouldPersist(): bool
    {
        return Config::get('bachs.database.sync', false);
    }

    /**
     * Log safe event metadata (never secrets or sensitive data).
     */
    protected function logEvent(WebhookEvent $event): void
    {
        if (! Config::get('bachs.logging.enabled', true)) {
            return;
        }

        Log::channel(Config::get('bachs.logging.channel'))->info('Webhook event received.', [
            'event_id' => $event->id(),
            'event_type' => $event->type(),
            'organization_id' => $event->organizationId(),
            'is_connect_event' => $event->isConnectEvent(),
        ]);
    }
}
