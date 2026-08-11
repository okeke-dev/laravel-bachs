<?php

namespace OkekeDev\Bachs\Resources;

/**
 * The Bachs balances resource.
 */
class Balances extends BachsResource
{
    /**
     * Fetch the account balances across currencies.
     *
     * @return array<mixed>
     */
    public static function get(): array
    {
        return static::defaultClient()->get('accounts/balances')->toArray();
    }
}
