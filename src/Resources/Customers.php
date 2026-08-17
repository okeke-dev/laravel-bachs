<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\Customer;
use OkekeDev\Bachs\Dto\PortalSession;

/**
 * The Bachs customers resource.
 */
class Customers extends BachsResource
{
    /**
     * Create a customer.
     *
     * @param  array<mixed>  $params
     */
    public static function create(array $params = [], ?string $idempotencyKey = null): Customer
    {
        return Customer::from(static::defaultClient()->post('customers', $params, $idempotencyKey)->toArray());
    }

    /**
     * List customers, optionally filtered/paginated via `limit`, `cursor`, or
     * `offset` query parameters.
     *
     * @param  array<mixed>  $params
     */
    public static function list(array $params = []): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get('customers', $params)->toArray())
            ->mapInto(Customer::class);
    }

    /**
     * Fetch a single customer.
     */
    public static function get(string $id): Customer
    {
        return Customer::from(static::defaultClient()->get("customers/{$id}")->toArray());
    }

    /**
     * Update a customer. Only provided fields are changed.
     *
     * @param  array<mixed>  $params
     */
    public static function update(string $id, array $params = [], ?string $idempotencyKey = null): Customer
    {
        return Customer::from(static::defaultClient()->patch("customers/{$id}", $params, $idempotencyKey)->toArray());
    }

    /**
     * Create a portal session for a customer.
     */
    public static function createPortalSession(string $id, ?string $idempotencyKey = null): PortalSession
    {
        return PortalSession::from(static::defaultClient()->post("customers/{$id}/portal-sessions", [], $idempotencyKey)->toArray());
    }
}
