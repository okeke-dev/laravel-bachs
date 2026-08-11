<?php

namespace OkekeDev\Bachs\Dto;

use OkekeDev\Bachs\ValueObjects\Money;

/**
 * The organization balance snapshot: one bucket per currency, plus a
 * consolidated USD total.
 */
final class Balance extends Dto
{
    public function accountId(): string
    {
        return $this->str('account_id') ?? '';
    }

    /**
     * @return list<BalanceBucket>
     */
    public function balances(): array
    {
        return array_map(fn (array $bucket) => BalanceBucket::from($bucket), $this->arr('balances'));
    }

    public function totalBalanceUsd(): ?Money
    {
        $amount = $this->str('total_balance_usd');

        return $amount === null ? null : Money::fromDecimal($amount, 'USD');
    }

    /**
     * Upcoming settlements grouped by day; empty when none are pending.
     *
     * @return array<mixed>
     */
    public function pendingSettlementsByDay(): array
    {
        return $this->arr('pending_settlements_by_day');
    }
}
