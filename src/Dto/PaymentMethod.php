<?php

namespace OkekeDev\Bachs\Dto;

use OkekeDev\Bachs\ValueObjects\Currency;

/**
 * A payment method Bachs supports (e.g. `card`, `bank_transfer`, `crypto`).
 */
final class PaymentMethod extends Dto
{
    public function id(): string
    {
        return $this->str('id') ?? '';
    }

    public function displayName(): string
    {
        return $this->str('display_name') ?? '';
    }

    public function icon(): string
    {
        return $this->str('icon') ?? '';
    }

    public function description(): string
    {
        return $this->str('description') ?? '';
    }

    /**
     * `fiat` or `crypto`.
     */
    public function type(): string
    {
        return $this->str('type') ?? '';
    }

    public function isFiat(): bool
    {
        return $this->type() === 'fiat';
    }

    public function isCrypto(): bool
    {
        return $this->type() === 'crypto';
    }

    public function enabledByDefault(): bool
    {
        return $this->bool('enabled_by_default', true);
    }

    /**
     * Currencies this method supports.
     *
     * @return list<Currency>
     */
    public function currencies(): array
    {
        return array_map(
            Currency::fromCode(...),
            array_values(array_filter($this->arr('currencies'), 'is_string')),
        );
    }

    public function supportsCurrency(Currency|string $currency): bool
    {
        $code = $currency instanceof Currency ? $currency->code() : strtoupper($currency);

        foreach ($this->currencies() as $supported) {
            if ($supported->code() === $code) {
                return true;
            }
        }

        return false;
    }
}
