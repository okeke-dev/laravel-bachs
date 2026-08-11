<?php

namespace OkekeDev\Bachs\ValueObjects;

use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;

/**
 * An ISO 4217 currency code, or a Bachs crypto code (e.g. `USDT_TRC20`).
 *
 * Codes are validated for shape (uppercase, 3+ characters, optional `_network`
 * suffix) rather than membership, so new Bachs codes keep working.
 *
 * @immutable
 */
final class Currency
{
    /**
     * Decimal places per ISO 4217 exponent for common currencies. Any code not
     * listed defaults to 2.
     *
     * @var array<string, int>
     */
    private const FIAT_DECIMALS = [
        'BHD' => 3, 'CLP' => 0, 'CNY' => 2, 'DJF' => 0, 'GHS' => 2,
        'JPY' => 0, 'KES' => 2, 'KWD' => 3, 'NGN' => 2, 'OMR' => 3,
        'RWF' => 0, 'TND' => 3, 'TZS' => 2, 'UGX' => 0, 'USD' => 2,
        'XAF' => 0, 'XOF' => 0, 'ZAR' => 2, 'ZMW' => 2,
    ];

    /**
     * Decimal places for known crypto codes. Unlisted crypto defaults to 2.
     *
     * @var array<string, int>
     */
    private const CRYPTO_DECIMALS = [
        'BTC' => 8, 'ETH' => 8, 'SOL' => 8, 'USDC' => 6, 'USDT' => 6,
    ];

    private function __construct(private readonly string $code) {}

    /**
     * Build a currency from a code, rejecting malformed input.
     *
     * @throws BachsInvalidArgumentException
     */
    public static function fromCode(string $code): self
    {
        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z0-9]{3,}(_[A-Z0-9]{2,12})?$/', $code)) {
            throw new BachsInvalidArgumentException(sprintf('Invalid currency code "%s".', $code));
        }

        return new self($code);
    }

    /**
     * Build a currency from a code, returning `null` when malformed.
     */
    public static function tryFromCode(string $code): ?self
    {
        try {
            return self::fromCode($code);
        } catch (BachsInvalidArgumentException) {
            return null;
        }
    }

    /**
     * The normalized (uppercase) currency code.
     */
    public function code(): string
    {
        return $this->code;
    }

    /**
     * Whether this is a cryptocurrency code (networked like `USDT_TRC20`, or a
     * known crypto asset like `BTC`).
     */
    public function isCrypto(): bool
    {
        return str_contains($this->code, '_') || isset(self::CRYPTO_DECIMALS[$this->code]);
    }

    /**
     * Whether this is a fiat currency.
     */
    public function isFiat(): bool
    {
        return ! $this->isCrypto();
    }

    /**
     * The number of decimal places the currency uses.
     */
    public function decimals(): int
    {
        return $this->isCrypto()
            ? (self::CRYPTO_DECIMALS[$this->code] ?? 2)
            : (self::FIAT_DECIMALS[$this->code] ?? 2);
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
