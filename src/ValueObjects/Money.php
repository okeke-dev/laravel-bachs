<?php

namespace OkekeDev\Bachs\ValueObjects;

use NumberFormatter;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;

/**
 * A decimal-string-backed money amount at its currency's precision.
 *
 * Backed by a string, never a float (D-01). Arithmetic is exact string
 * arithmetic via bcmath and never rounds beyond the input precision. Floats
 * are rejected at the boundary.
 *
 * @immutable
 */
final class Money
{
    private function __construct(
        private readonly string $amount,
        private readonly Currency $currency,
    ) {
        if (! preg_match('/^-?\d+(\.\d+)?$/', $amount)) {
            throw new BachsInvalidArgumentException(sprintf('Invalid money amount "%s".', $amount));
        }
    }

    /**
     * Build a money value from a decimal string (or integer). Floats are
     * rejected: PHP floats cannot represent money safely.
     *
     *
     * @throws BachsInvalidArgumentException
     */
    public static function fromDecimal(int|string|float $amount, Currency|string $currency): self
    {
        if (is_float($amount)) {
            throw new BachsInvalidArgumentException('Money amounts must be decimal strings, never floats.');
        }

        if (is_int($amount)) {
            $amount = (string) $amount;
        }

        $currency = $currency instanceof Currency ? $currency : Currency::fromCode($currency);

        return new self($amount, $currency);
    }

    /**
     * The amount as a decimal string at the currency's precision.
     */
    public function amount(): string
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    /**
     * Whether the amount is zero.
     */
    public function isZero(): bool
    {
        return bccomp($this->amount, '0', $this->scale()) === 0;
    }

    /**
     * Whether the amount is negative.
     */
    public function isNegative(): bool
    {
        return str_starts_with($this->amount, '-');
    }

    public function equals(self $other): bool
    {
        if (! $this->currency->equals($other->currency)) {
            return false;
        }

        return bccomp($this->amount, $other->amount, max($this->scale(), $other->scale())) === 0;
    }

    /**
     * Add another amount of the same currency, returning a new `Money`.
     *
     * @throws BachsInvalidArgumentException when currencies differ
     */
    public function add(int|string|self $other): self
    {
        $other = $this->coerce($other);

        return new self(bcadd($this->amount, $other->amount, max($this->scale(), $other->scale())), $this->currency);
    }

    /**
     * Subtract another amount of the same currency, returning a new `Money`.
     *
     * @throws BachsInvalidArgumentException when currencies differ
     */
    public function subtract(int|string|self $other): self
    {
        $other = $this->coerce($other);

        return new self(bcsub($this->amount, $other->amount, max($this->scale(), $other->scale())), $this->currency);
    }

    /**
     * Format for display. Crypto amounts render as `<amount> <code>` because
     * `NumberFormatter` has no symbol for them; fiat uses locale-aware
     * currency formatting.
     */
    public function format(?string $locale = null): string
    {
        if ($this->currency->isCrypto()) {
            return sprintf('%s %s', $this->amount, $this->currency->code());
        }

        $formatter = new NumberFormatter($locale ?? 'en_US', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency((float) $this->amount, $this->currency->code());
    }

    public function __toString(): string
    {
        return $this->amount;
    }

    private function coerce(int|string|self $other): self
    {
        if ($other instanceof self) {
            if (! $other->currency->equals($this->currency)) {
                throw new BachsInvalidArgumentException(sprintf(
                    'Cannot combine %s and %s: currencies differ.',
                    $this->currency->code(),
                    $other->currency->code(),
                ));
            }

            return $other;
        }

        return self::fromDecimal($other, $this->currency);
    }

    private function scale(): int
    {
        $position = strrpos($this->amount, '.');

        return $position === false ? 0 : strlen($this->amount) - $position - 1;
    }
}
