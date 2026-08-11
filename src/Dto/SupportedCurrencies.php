<?php

namespace OkekeDev\Bachs\Dto;

use OkekeDev\Bachs\ValueObjects\Currency;

/**
 * The set of supported currencies, grouped by type (`{ fiat, crypto }`).
 */
final class SupportedCurrencies extends Dto
{
    /**
     * @return list<Currency>
     */
    public function fiat(): array
    {
        return $this->codes('fiat');
    }

    /**
     * @return list<Currency>
     */
    public function crypto(): array
    {
        return $this->codes('crypto');
    }

    /**
     * @return list<Currency>
     */
    public function all(): array
    {
        return array_merge($this->fiat(), $this->crypto());
    }

    public function isEmpty(): bool
    {
        return $this->all() === [];
    }

    public function supports(Currency|string $currency): bool
    {
        $code = $currency instanceof Currency ? $currency->code() : strtoupper($currency);

        foreach ($this->all() as $supported) {
            if ($supported->code() === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Currency>
     */
    private function codes(string $group): array
    {
        return array_map(
            Currency::fromCode(...),
            array_values(array_filter($this->arr($group), 'is_string')),
        );
    }
}
