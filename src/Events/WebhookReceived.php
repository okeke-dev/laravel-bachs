<?php

namespace OkekeDev\Bachs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use OkekeDev\Bachs\Webhooks\WebhookEvent;

/**
 * Fired when any webhook is received, before processing.
 */
class WebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WebhookEvent $webhookEvent
    ) {}
}
