<?php

namespace OkekeDev\Bachs\Dto;

use OkekeDev\Bachs\ValueObjects\Currency;
use OkekeDev\Bachs\ValueObjects\Money;

/**
 * One currency bucket of an organization balance.
 */
final class BalanceBucket extends Dto
{
    public function currency(): Currency
    {
        return $this->currencyFrom('currency');
    }

    public function availableBalance(): ?Money
    {
        return $this->money('available_balance');
    }

    public function pendingBalance(): ?Money
    {
        return $this->money('pending_balance');
    }

    private function money(string $key): ?Money
    {
        $amount = $this->str($key);

        return $amount === null ? null : Money::fromDecimal($amount, $this->currency());
    }
}
