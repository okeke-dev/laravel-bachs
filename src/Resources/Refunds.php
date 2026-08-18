<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\Refund;

/**
 * The Bachs refunds resource.
 */
class Refunds extends BachsResource
{
    /**
     * Create a refund.
     *
     * @param  array<string, mixed>  $params
     */
    public static function create(array $params = [], ?string $idempotencyKey = null): Refund
    {
        return Refund::from(static::defaultClient()->post('refunds', $params, $idempotencyKey)->toArray());
    }

    /**
     * List refunds, optionally filtered/paginated via `limit`, `cursor`, or
     * `offset` query parameters.
     *
     * @param  array<string, mixed>  $params
     */
    public static function list(array $params = []): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get('refunds', $params)->toArray())
            ->mapInto(Refund::class);
    }

    /**
     * Fetch a single refund.
     */
    public static function get(string $id): Refund
    {
        return Refund::from(static::defaultClient()->get("refunds/{$id}")->toArray());
    }

    /**
     * Fetch refunds by charge ID.
     */
    public static function getByCharge(string $chargeId): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get("refunds/by-charge/{$chargeId}")->toArray())
            ->mapInto(Refund::class);
    }
}
