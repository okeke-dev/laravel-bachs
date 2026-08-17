<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\Subscription;

/**
 * The Bachs subscriptions resource.
 */
class Subscriptions extends BachsResource
{
    /**
     * List subscriptions, optionally filtered/paginated via `limit`, `cursor`, or
     * `offset` query parameters.
     *
     * @param  array<string, mixed>  $params
     */
    public static function list(array $params = []): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get('subscriptions', $params)->toArray())
            ->mapInto(Subscription::class);
    }

    /**
     * Fetch a single subscription.
     */
    public static function get(string $id): Subscription
    {
        return Subscription::from(static::defaultClient()->get("subscriptions/{$id}")->toArray());
    }

    /**
     * Update a subscription. Only one intent per update:
     * change plan, move trial, change payment method, or update metadata.
     *
     * @param  array<string, mixed>  $params
     */
    public static function update(string $id, array $params = [], ?string $idempotencyKey = null): Subscription
    {
        return Subscription::from(static::defaultClient()->patch("subscriptions/{$id}", $params, $idempotencyKey)->toArray());
    }

    /**
     * Cancel a subscription immediately.
     */
    public static function cancel(string $id, ?string $idempotencyKey = null): Subscription
    {
        return Subscription::from(static::defaultClient()->delete("subscriptions/{$id}", [], $idempotencyKey)->toArray());
    }
}
