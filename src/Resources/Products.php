<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Collections\PaginatedCollection;

/**
 * The Bachs products resource.
 */
class Products extends BachsResource
{
    /**
     * Create a product.
     *
     * @param  array<mixed>  $params
     * @return array<mixed>
     */
    public static function create(array $params = [], ?string $idempotencyKey = null): array
    {
        return static::defaultClient()->post('products', $params, $idempotencyKey)->toArray();
    }

    /**
     * List products, optionally filtered/paginated via `limit`, `cursor`, or
     * `offset` query parameters.
     *
     * @param  array<mixed>  $params
     */
    public static function list(array $params = []): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get('products', $params)->toArray());
    }

    /**
     * Fetch a single product.
     *
     * @return array<mixed>
     */
    public static function get(string $id): array
    {
        return static::defaultClient()->get("products/{$id}")->toArray();
    }

    /**
     * Update a product. Only provided fields are changed.
     *
     * @param  array<mixed>  $params
     * @return array<mixed>
     */
    public static function update(string $id, array $params = [], ?string $idempotencyKey = null): array
    {
        return static::defaultClient()->patch("products/{$id}", $params, $idempotencyKey)->toArray();
    }

    /**
     * Archive a product, removing it from new checkouts.
     *
     * @return array<mixed>
     */
    public static function archive(string $id, ?string $idempotencyKey = null): array
    {
        return static::defaultClient()->post("products/{$id}/archive", [], $idempotencyKey)->toArray();
    }

    /**
     * Unarchive a product, making it available for checkout again.
     *
     * @return array<mixed>
     */
    public static function unarchive(string $id, ?string $idempotencyKey = null): array
    {
        return static::defaultClient()->post("products/{$id}/unarchive", [], $idempotencyKey)->toArray();
    }
}
