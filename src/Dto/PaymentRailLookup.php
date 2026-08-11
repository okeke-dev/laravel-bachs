<?php

namespace OkekeDev\Bachs\Dto;

use OkekeDev\Bachs\ValueObjects\Currency;

/**
 * The available rails for a payment method + currency lookup.
 */
final class PaymentRailLookup extends Dto
{
    public function paymentMethod(): string
    {
        return $this->str('payment_method') ?? '';
    }

    public function currency(): ?Currency
    {
        $code = $this->str('currency');

        return $code === null ? null : Currency::fromCode($code);
    }

    public function countryCode(): ?string
    {
        return $this->str('country_code');
    }

    /**
     * @return list<PaymentRailOption>
     */
    public function rails(): array
    {
        return array_map(fn (array $rail) => PaymentRailOption::from($rail), $this->arr('rails'));
    }
}
