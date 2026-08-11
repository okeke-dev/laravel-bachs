<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Collections\PaginatedCollection;

/**
 * The Bachs payment methods resource.
 */
class PaymentMethods extends BachsResource
{
    /**
     * List saved payment methods, optionally paginated.
     *
     * @param  array<mixed>  $params
     */
    public static function list(array $params = []): PaginatedCollection
    {
        return PaginatedCollection::fromPayload(static::defaultClient()->get('payment-methods', $params)->toArray());
    }

    /**
     * List the payment rails supported by the account.
     *
     * @return array<mixed>
     */
    public static function rails(): array
    {
        return static::defaultClient()->get('payment-methods/rails')->toArray();
    }
}
