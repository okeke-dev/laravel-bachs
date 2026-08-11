<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Dto\SupportedCurrencies;

/**
 * The Bachs currencies resource.
 */
class Currencies extends BachsResource
{
    /**
     * List the currencies the account can use, grouped by type.
     */
    public static function supported(): SupportedCurrencies
    {
        return SupportedCurrencies::from(static::defaultClient()->get('currencies/supported')->toArray());
    }

    /**
     * List the currencies that support payouts/withdrawals, grouped by type.
     */
    public static function payoutSupported(): SupportedCurrencies
    {
        return SupportedCurrencies::from(static::defaultClient()->get('currencies/payout-supported')->toArray());
    }
}
