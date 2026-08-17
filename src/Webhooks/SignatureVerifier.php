<?php

namespace OkekeDev\Bachs\Webhooks;

use OkekeDev\Bachs\Exceptions\BachsWebhookInvalidSignatureException;
use OkekeDev\Bachs\Exceptions\BachsWebhookStaleTimestampException;

/**
 * Verifies the signature of incoming Bachs webhook deliveries.
 *
 * Uses HMAC-SHA256 with constant-time comparison to prevent timing attacks.
 * The signature is computed as: HMAC-SHA256(secret, "{timestamp}.{raw_body}")
 */
class SignatureVerifier
{
    /**
     * Verify the webhook signature.
     *
     * @param  string  $rawBody  The raw request body
     * @param  string|null  $timestamp  The X-Bachs-Timestamp header value
     * @param  string|null  $signature  The X-Bachs-Signature header value
     * @param  string|null  $secret  The webhook signing secret (falls back to config)
     * @param  int|null  $tolerance  Maximum age in seconds (falls back to config)
     *
     * @throws BachsWebhookInvalidSignatureException
     * @throws BachsWebhookStaleTimestampException
     */
    public function verify(
        string $rawBody,
        ?string $timestamp,
        ?string $signature,
        ?string $secret = null,
        ?int $tolerance = null
    ): void {
        $secret = $secret ?? config('bachs.webhook.secret');
        $tolerance = $tolerance ?? config('bachs.webhook.tolerance', 300);

        if ($secret === null || $secret === '') {
            throw new \InvalidArgumentException(
                'No webhook signing secret is configured. Set BACHS_WEBHOOK_SECRET or configure bachs.webhook.secret.'
            );
        }

        if ($timestamp === null || $timestamp === '') {
            throw new BachsWebhookInvalidSignatureException(
                'Missing X-Bachs-Timestamp header.'
            );
        }

        if ($signature === null || $signature === '') {
            throw new BachsWebhookInvalidSignatureException(
                'Missing X-Bachs-Signature header.'
            );
        }

        // Validate timestamp is numeric (unix seconds)
        if (! ctype_digit($timestamp)) {
            throw new BachsWebhookInvalidSignatureException(
                'Invalid X-Bachs-Timestamp header format.'
            );
        }

        // Check timestamp staleness (replay protection)
        $timestampInt = (int) $timestamp;
        $now = time();

        if (abs($now - $timestampInt) > $tolerance) {
            throw new BachsWebhookStaleTimestampException(
                "Webhook timestamp is outside the allowed tolerance of {$tolerance} seconds."
            );
        }

        // Compute expected signature
        $payload = "{$timestamp}.{$rawBody}";
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Constant-time comparison to prevent timing attacks
        if (! hash_equals($expectedSignature, $signature)) {
            throw new BachsWebhookInvalidSignatureException(
                'Webhook signature verification failed.'
            );
        }
    }
}
