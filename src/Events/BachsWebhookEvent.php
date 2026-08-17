<?php

namespace OkekeDev\Bachs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use OkekeDev\Bachs\Webhooks\WebhookEvent;

/**
 * Base class for all Bachs webhook events.
 */
abstract class BachsWebhookEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WebhookEvent $webhookEvent
    ) {}

    /**
     * Get the event ID from the webhook payload.
     */
    public function eventId(): string
    {
        return $this->webhookEvent->id();
    }

    /**
     * Get the event type from the webhook payload.
     */
    public function eventType(): string
    {
        return $this->webhookEvent->type();
    }

    /**
     * Get the event data from the webhook payload.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->webhookEvent->data();
    }
}
