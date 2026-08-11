<?php

namespace OkekeDev\Bachs\Resources;

/**
 * The Bachs currencies resource.
 */
class Currencies extends BachsResource
{
    /**
     * List the currencies the account can use.
     *
     * @return array<mixed>
     */
    public static function supported(): array
    {
        return static::defaultClient()->get('currencies/supported')->toArray();
    }

    /**
     * List the currencies that support payouts.
     *
     * @return array<mixed>
     */
    public static function payoutSupported(): array
    {
        return static::defaultClient()->get('currencies/payout-supported')->toArray();
    }
}
