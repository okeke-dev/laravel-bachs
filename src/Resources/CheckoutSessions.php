<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Dto\CheckoutSession;

/**
 * The Bachs checkout sessions resource.
 */
class CheckoutSessions extends BachsResource
{
    /**
     * Create a checkout session.
     *
     * @param  array<string, mixed>  $params
     */
    public static function create(array $params = [], ?string $idempotencyKey = null): CheckoutSession
    {
        return CheckoutSession::from(static::defaultClient()->post('checkout-sessions', $params, $idempotencyKey)->toArray());
    }

    /**
     * Fetch a single checkout session.
     */
    public static function get(string $id): CheckoutSession
    {
        return CheckoutSession::from(static::defaultClient()->get("checkout-sessions/{$id}")->toArray());
    }
}
