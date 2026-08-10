<?php

namespace OkekeDev\Bachs\Exceptions;

class BachsRateLimitException extends BachsApiException
{
    /**
     * Seconds to wait before retrying, when the API tells us.
     */
    public function retryAfter(): ?int
    {
        $retryAfter = $this->headerValue('Retry-After');

        if ($retryAfter !== null && ctype_digit($retryAfter)) {
            return (int) $retryAfter;
        }

        $reset = $this->headerValue('X-RateLimit-Reset');

        if ($reset !== null && ctype_digit($reset)) {
            return max(0, (int) $reset - time());
        }

        return null;
    }

    /**
     * Case-insensitive lookup on the stored response headers.
     */
    protected function headerValue(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp((string) $key, $name) !== 0) {
                continue;
            }

            return (string) reset($value);
        }

        return null;
    }
}
