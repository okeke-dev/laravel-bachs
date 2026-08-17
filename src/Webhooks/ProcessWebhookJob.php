<?php

namespace OkekeDev\Bachs\Webhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for processing webhook events on a queue.
 *
 * This job is retry-safe because:
 * 1. The event ID is checked for duplicates before processing
 * 2. Database transactions ensure atomicity
 * 3. Failed jobs can be retried without side effects
 */
class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    public function __construct(
        public readonly WebhookEvent $event
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookProcessor $processor): void
    {
        try {
            $processor->process($this->event);
        } catch (\Throwable $e) {
            Log::error('Failed to process webhook event.', [
                'event_id' => $this->event->id(),
                'event_type' => $this->event->type(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
