<?php

namespace OkekeDev\Bachs\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use OkekeDev\Bachs\Exceptions\BachsWebhookInvalidSignatureException;
use OkekeDev\Bachs\Exceptions\BachsWebhookStaleTimestampException;
use OkekeDev\Bachs\Webhooks\ProcessWebhookJob;
use OkekeDev\Bachs\Webhooks\SignatureVerifier;
use OkekeDev\Bachs\Webhooks\WebhookEvent;
use OkekeDev\Bachs\Webhooks\WebhookProcessor;

/**
 * Handles incoming Bachs webhook deliveries.
 *
 * The controller acknowledges the delivery immediately (200) before
 * processing the event to prevent timeouts and retries for slow logic.
 */
class WebhookController
{
    public function __construct(
        protected SignatureVerifier $verifier,
        protected WebhookProcessor $processor,
    ) {}

    /**
     * Handle an incoming webhook delivery.
     *
     * Flow: verify signature → parse payload → acknowledge → process
     */
    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $timestamp = $request->header('X-Bachs-Timestamp');
        $signature = $request->header('X-Bachs-Signature');

        // Step 1: Verify signature (throws on failure)
        try {
            $this->verifier->verify(
                rawBody: $rawBody,
                timestamp: $timestamp,
                signature: $signature,
            );
        } catch (BachsWebhookInvalidSignatureException $e) {
            Log::warning('Webhook signature verification failed.', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse(['error' => 'Invalid signature'], 400);
        } catch (BachsWebhookStaleTimestampException $e) {
            Log::warning('Webhook timestamp is stale.', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse(['error' => 'Stale timestamp'], 400);
        }

        // Step 2: Parse payload
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            Log::warning('Webhook payload is not valid JSON.');

            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        // Step 3: Create event object
        $event = WebhookEvent::fromPayload($payload);

        // Step 4: Acknowledge immediately (200) before processing
        // This prevents Bachs from retrying while we process
        $response = new JsonResponse(['received' => true], 200);

        // Step 5: Process the event (may be queued)
        $queueConnection = Config::get('bachs.webhook.queue');

        if ($queueConnection !== null) {
            // Queue the processing for later
            Bus::dispatch(
                new ProcessWebhookJob($event)
            )->onConnection($queueConnection);
        } else {
            // Process synchronously
            $this->processor->process($event);
        }

        return $response;
    }
}
