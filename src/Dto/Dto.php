<?php

namespace OkekeDev\Bachs\Dto;

use OkekeDev\Bachs\ValueObjects\Currency;

/**
 * Base for read-oriented, immutable DTOs hydrated from a Bachs payload.
 *
 * DTOs are constructed from the raw array and expose typed accessors. The raw
 * payload stays reachable via {@see self::toArray()} / {@see self::raw()} so a
 * field Bachs adds later is never lost.
 *
 * @immutable
 */
abstract class Dto
{
    /**
     * @param  array<mixed>  $data
     */
    final public function __construct(protected readonly array $data) {}

    /**
     * @param  array<mixed>  $data
     */
    public static function from(array $data): static
    {
        return new static($data);
    }

    /**
     * The unmodified API payload this DTO was built from.
     *
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Alias of {@see self::toArray()} for forward-compatibility.
     *
     * @return array<mixed>
     */
    public function raw(): array
    {
        return $this->data;
    }

    protected function str(string $key, ?string $default = null): ?string
    {
        $value = $this->data[$key] ?? $default;

        return $value === null ? null : (string) $value;
    }

    protected function int(string $key, ?int $default = null): ?int
    {
        $value = $this->data[$key] ?? $default;

        return $value === null ? null : (int) $value;
    }

    protected function bool(string $key, bool $default = false): bool
    {
        return (bool) ($this->data[$key] ?? $default);
    }

    /**
     * @param  array<mixed>  $default
     * @return array<mixed>
     */
    protected function arr(string $key, array $default = []): array
    {
        $value = $this->data[$key] ?? $default;

        return is_array($value) ? $value : $default;
    }

    protected function currencyFrom(string $key, ?Currency $default = null): Currency
    {
        $code = $this->str($key);

        return $code === null
            ? ($default ?? Currency::fromCode(''))
            : Currency::fromCode($code);
    }
}
