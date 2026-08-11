<?php

namespace OkekeDev\Bachs\ValueObjects;

use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;

/**
 * A billing cadence (or trial period) of `{ interval, frequency }`, mirroring
 * the Bachs shape exactly (e.g. `{ interval: 'month', frequency: 3 }`).
 *
 * @immutable
 */
final class Cadence
{
    /**
     * @var list<string>
     */
    private const INTERVALS = ['day', 'week', 'month', 'year'];

    private function __construct(
        private readonly string $interval,
        private readonly int $frequency,
    ) {
        if (! in_array($interval, self::INTERVALS, true)) {
            throw new BachsInvalidArgumentException(sprintf(
                'Invalid cadence interval "%s". Expected one of: %s.',
                $interval,
                implode(', ', self::INTERVALS),
            ));
        }

        if ($frequency < 1) {
            throw new BachsInvalidArgumentException('Cadence frequency must be at least 1.');
        }
    }

    /**
     * Build a cadence from a payload, returning `null` when the keys are absent.
     *
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $interval = $data['interval'] ?? null;
        $frequency = $data['frequency'] ?? null;

        if (! is_string($interval) || ! is_int($frequency)) {
            return null;
        }

        return new self($interval, $frequency);
    }

    /**
     * The unit of time: `day`, `week`, `month`, or `year`.
     */
    public function interval(): string
    {
        return $this->interval;
    }

    /**
     * How many intervals make up one cycle.
     */
    public function frequency(): int
    {
        return $this->frequency;
    }

    /**
     * @return array{interval: string, frequency: int}
     */
    public function toArray(): array
    {
        return ['interval' => $this->interval, 'frequency' => $this->frequency];
    }

    /**
     * Human-readable form, e.g. "every 3 months" or "every month".
     */
    public function __toString(): string
    {
        $unit = $this->frequency === 1 ? $this->interval : $this->interval.'s';

        return sprintf('every %d %s', $this->frequency, $unit);
    }
}
