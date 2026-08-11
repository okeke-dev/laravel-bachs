<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\Product;

/**
 * The Bachs products resource.
 */
class Products extends BachsResource
{
    /**
     * Create a product.
     *
     * @param  array<mixed>  $params
     */
    public static function create(array $params = [], ?string $idempotencyKey = null): Product
    {
        return Product::from(static::defaultClient()->post('products', $params, $idempotencyKey)->toArray());
    }

    /**
     * List products, optionally filtered/paginated via `limit`, `cursor`, or
     * `offset` query parameters.
     *
     * @param  array<mixed>  $params
     */
    public static function list(array $params = []): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get('products', $params)->toArray())
            ->mapInto(Product::class);
    }

    /**
     * Fetch a single product.
     */
    public static function get(string $id): Product
    {
        return Product::from(static::defaultClient()->get("products/{$id}")->toArray());
    }

    /**
     * Update a product. Only provided fields are changed.
     *
     * @param  array<mixed>  $params
     */
    public static function update(string $id, array $params = [], ?string $idempotencyKey = null): Product
    {
        return Product::from(static::defaultClient()->patch("products/{$id}", $params, $idempotencyKey)->toArray());
    }

    /**
     * Archive a product, removing it from new checkouts.
     */
    public static function archive(string $id, ?string $idempotencyKey = null): Product
    {
        return Product::from(static::defaultClient()->post("products/{$id}/archive", [], $idempotencyKey)->toArray());
    }

    /**
     * Unarchive a product, making it available for checkout again.
     */
    public static function unarchive(string $id, ?string $idempotencyKey = null): Product
    {
        return Product::from(static::defaultClient()->post("products/{$id}/unarchive", [], $idempotencyKey)->toArray());
    }
}
