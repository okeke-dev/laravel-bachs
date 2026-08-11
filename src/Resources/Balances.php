<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Dto\Balance;

/**
 * The Bachs balances resource.
 */
class Balances extends BachsResource
{
    /**
     * Fetch the organization's balances across currencies.
     */
    public static function get(): Balance
    {
        return Balance::from(static::defaultClient()->get('accounts/balances')->toArray());
    }
}
