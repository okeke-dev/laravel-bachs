<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\Payment;

/**
 * The Bachs payments resource.
 */
class Payments extends BachsResource
{
    /**
     * List payments, optionally filtered/paginated via `limit`, `cursor`, or
     * `offset` query parameters.
     *
     * @param  array<string, mixed>  $params
     */
    public static function list(array $params = []): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get('payments', $params)->toArray())
            ->mapInto(Payment::class);
    }

    /**
     * Fetch a single payment.
     */
    public static function get(string $id): Payment
    {
        return Payment::from(static::defaultClient()->get("payments/{$id}")->toArray());
    }

    /**
     * Fetch a payment by its charge ID.
     */
    public static function getByCharge(string $chargeId): Payment
    {
        return Payment::from(static::defaultClient()->get("payments/charges/{$chargeId}")->toArray());
    }
}
