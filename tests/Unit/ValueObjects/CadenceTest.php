<?php

use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\ValueObjects\Cadence;

it('builds from a payload', function () {
    $cadence = Cadence::fromArray(['interval' => 'month', 'frequency' => 3]);

    expect($cadence)->not->toBeNull()
        ->and($cadence->interval())->toBe('month')
        ->and($cadence->frequency())->toBe(3);
});

it('returns null when the cadence keys are absent', function () {
    expect(Cadence::fromArray([]))->toBeNull()
        ->and(Cadence::fromArray(['interval' => 'month']))->toBeNull()
        ->and(Cadence::fromArray(['interval' => 1, 'frequency' => 2]))->toBeNull();
});

it('rejects invalid intervals and frequencies', function () {
    Cadence::fromArray(['interval' => 'fortnight', 'frequency' => 1]);
})->throws(BachsInvalidArgumentException::class);

it('stringifies to a human-readable cadence', function () {
    expect((string) Cadence::fromArray(['interval' => 'month', 'frequency' => 1]))->toBe('every 1 month')
        ->and((string) Cadence::fromArray(['interval' => 'day', 'frequency' => 14]))->toBe('every 14 days');
});
