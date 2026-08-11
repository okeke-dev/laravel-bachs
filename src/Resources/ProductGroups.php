<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\ProductGroup;

/**
 * The Bachs product groups resource.
 */
class ProductGroups extends BachsResource
{
    /**
     * Create a product group.
     *
     * @param  array<mixed>  $params
     */
    public static function create(array $params = [], ?string $idempotencyKey = null): ProductGroup
    {
        return ProductGroup::from(static::defaultClient()->post('product-groups', $params, $idempotencyKey)->toArray());
    }

    /**
     * List product groups, optionally paginated.
     *
     * @param  array<mixed>  $params
     */
    public static function list(array $params = []): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get('product-groups', $params)->toArray())
            ->mapInto(ProductGroup::class);
    }

    /**
     * Fetch a single product group.
     */
    public static function get(string $id): ProductGroup
    {
        return ProductGroup::from(static::defaultClient()->get("product-groups/{$id}")->toArray());
    }

    /**
     * Update a product group. Only provided fields are changed.
     *
     * @param  array<mixed>  $params
     */
    public static function update(string $id, array $params = [], ?string $idempotencyKey = null): ProductGroup
    {
        return ProductGroup::from(static::defaultClient()->patch("product-groups/{$id}", $params, $idempotencyKey)->toArray());
    }

    /**
     * Delete a product group (`204 No Content` on success).
     */
    public static function delete(string $id, ?string $idempotencyKey = null): void
    {
        static::defaultClient()->delete("product-groups/{$id}", [], $idempotencyKey);
    }
}
