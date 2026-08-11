<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Dto\PaymentMethod;
use OkekeDev\Bachs\Dto\PaymentRailLookup;

/**
 * The Bachs payment methods resource.
 */
class PaymentMethods extends BachsResource
{
    /**
     * List the payment methods Bachs supports for this environment.
     *
     * @param  array<mixed>  $params
     * @return list<PaymentMethod>
     */
    public static function list(array $params = []): array
    {
        $payload = static::defaultClient()->get('payment-methods', $params)->toArray();
        $items = is_array($payload['payment_methods'] ?? null) ? $payload['payment_methods'] : [];

        return array_map(fn (mixed $item) => PaymentMethod::from(is_array($item) ? $item : []), $items);
    }

    /**
     * List the payment rails available for a payment method + currency
     * combination.
     *
     * @param  array<mixed>  $params  e.g. `['payment_method' => 'card', 'currency' => 'NGN']`
     */
    public static function rails(array $params = []): PaymentRailLookup
    {
        return PaymentRailLookup::from(static::defaultClient()->get('payment-methods/rails', $params)->toArray());
    }
}
