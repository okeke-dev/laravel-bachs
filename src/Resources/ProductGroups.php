<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Collections\PaginatedCollection;

/**
 * The Bachs product groups resource.
 */
class ProductGroups extends BachsResource
{
    /**
     * Create a product group.
     *
     * @param  array<mixed>  $params
     * @return array<mixed>
     */
    public static function create(array $params = [], ?string $idempotencyKey = null): array
    {
        return static::defaultClient()->post('product-groups', $params, $idempotencyKey)->toArray();
    }

    /**
     * List product groups, optionally paginated.
     *
     * @param  array<mixed>  $params
     */
    public static function list(array $params = []): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get('product-groups', $params)->toArray());
    }

    /**
     * Fetch a single product group.
     *
     * @return array<mixed>
     */
    public static function get(string $id): array
    {
        return static::defaultClient()->get("product-groups/{$id}")->toArray();
    }

    /**
     * Update a product group. Only provided fields are changed.
     *
     * @param  array<mixed>  $params
     * @return array<mixed>
     */
    public static function update(string $id, array $params = [], ?string $idempotencyKey = null): array
    {
        return static::defaultClient()->patch("product-groups/{$id}", $params, $idempotencyKey)->toArray();
    }

    /**
     * Delete a product group.
     *
     * @return array<mixed>
     */
    public static function delete(string $id, ?string $idempotencyKey = null): array
    {
        return static::defaultClient()->delete("product-groups/{$id}", [], $idempotencyKey)->toArray();
    }
}
