<?php

namespace OkekeDev\Bachs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use OkekeDev\Bachs\Webhooks\WebhookEvent;

/**
 * Fired after a webhook event has been processed and dispatched.
 */
class WebhookEventDispatched
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WebhookEvent $webhookEvent
    ) {}
}
