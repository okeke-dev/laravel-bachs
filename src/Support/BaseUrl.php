<?php

namespace OkekeDev\Bachs\Support;

use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;

class BaseUrl
{
    public const SANDBOX = 'https://sandbox-api.bachs.io/v1';

    public const LIVE = 'https://api.bachs.io/v1';

    /**
     * Resolve the base URL for a connection configuration.
     *
     * Precedence:
     *  1. An explicit `base_url`.
     *  2. The `env` value (`sandbox` | `live`).
     *  3. The secret key prefix (`sk_sandbox_` => sandbox, otherwise live),
     *     only when `env` is not configured.
     *
     * When `env` is configured, the secret key prefix is used as a safety
     * check: a contradiction is a configuration error and throws, protecting
     * against accidentally charging real money.
     *
     * @param  array<string, mixed>  $config
     */
    public static function resolve(array $config): string
    {
        if (isset($config['base_url']) && is_string($config['base_url']) && $config['base_url'] !== '') {
            return self::trim($config['base_url']);
        }

        $env = strtolower(trim((string) ($config['env'] ?? '')));
        $secret = (string) ($config['secret'] ?? '');

        if ($env === '') {
            return str_starts_with($secret, 'sk_sandbox_') ? self::SANDBOX : self::LIVE;
        }

        if (str_starts_with($secret, 'sk_')) {
            $fromSecret = str_starts_with($secret, 'sk_sandbox_') ? 'sandbox' : 'live';

            if ($fromSecret !== $env) {
                throw new BachsInvalidArgumentException(sprintf(
                    'Bachs connection mismatch: BACHS_ENV is [%s] but the secret key prefix is [%s].',
                    $env,
                    $fromSecret,
                ));
            }
        }

        return $env === 'live' ? self::LIVE : self::SANDBOX;
    }

    private static function trim(string $url): string
    {
        return rtrim($url, '/');
    }
}
