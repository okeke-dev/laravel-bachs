<?php

namespace OkekeDev\Bachs\Dto;

use OkekeDev\Bachs\ValueObjects\Currency;
use OkekeDev\Bachs\ValueObjects\Money;

/**
 * A Bachs price. `fixed` prices carry `amount`; `free` carry none; `custom`
 * prices may carry `preset_amount`, `minimum_amount`, and `maximum_amount`.
 */
final class Price extends Dto
{
    public function currency(): Currency
    {
        return $this->currencyFrom('currency');
    }

    /**
     * `fixed`, `free`, or `custom`.
     */
    public function priceType(): string
    {
        return $this->str('price_type') ?? 'fixed';
    }

    public function isFixed(): bool
    {
        return $this->priceType() === 'fixed';
    }

    public function isFree(): bool
    {
        return $this->priceType() === 'free';
    }

    public function isCustom(): bool
    {
        return $this->priceType() === 'custom';
    }

    public function amount(): ?Money
    {
        return $this->money('amount');
    }

    public function presetAmount(): ?Money
    {
        return $this->money('preset_amount');
    }

    public function minimumAmount(): ?Money
    {
        return $this->money('minimum_amount');
    }

    public function maximumAmount(): ?Money
    {
        return $this->money('maximum_amount');
    }

    /**
     * Per-currency pricing options, as raw payloads.
     *
     * @return array<mixed>
     */
    public function currencyOptions(): array
    {
        return $this->arr('currency_options');
    }

    private function money(string $key): ?Money
    {
        $amount = $this->str($key);

        return $amount === null ? null : Money::fromDecimal($amount, $this->currency());
    }
}
