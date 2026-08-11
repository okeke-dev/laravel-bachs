<?php

namespace OkekeDev\Bachs\Support;

use Illuminate\Http\Client\Response;

final class RetryDelay
{
    /**
     * Compute how long to wait before the next attempt, in milliseconds.
     *
     * A 429 response carrying a `Retry-After` header (or an
     * `X-RateLimit-Reset` timestamp) is honored verbatim. Otherwise the delay
     * grows exponentially from `retry.sleep_ms` by `retry.multiplier`,
     * capped at `retry.max_sleep_ms`.
     *
     * @param  array<string, mixed>  $retry
     */
    public static function milliseconds(int $attempt, ?Response $response = null, array $retry = []): int
    {
        if ($response !== null && $response->status() === 429) {
            $retryAfter = self::retryAfterMilliseconds($response);

            if ($retryAfter !== null) {
                return $retryAfter;
            }
        }

        $base = max(0, (int) ($retry['sleep_ms'] ?? 100));
        $multiplier = max(1.0, (float) ($retry['multiplier'] ?? 2.0));
        $max = max(1, (int) ($retry['max_sleep_ms'] ?? 5000));

        $delay = (int) round($base * ($multiplier ** ($attempt - 1)));

        return min($max, $delay);
    }

    private static function retryAfterMilliseconds(Response $response): ?int
    {
        $retryAfter = $response->header('Retry-After');

        if ($retryAfter !== '' && is_numeric($retryAfter)) {
            return max(0, (int) round((float) $retryAfter * 1000));
        }

        $reset = $response->header('X-RateLimit-Reset');

        if ($reset !== '' && is_numeric($reset)) {
            return max(0, (int) round(((float) $reset - (float) time()) * 1000));
        }

        return null;
    }
}
