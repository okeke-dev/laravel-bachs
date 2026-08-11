<?php

namespace OkekeDev\Bachs\Support;

use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;

class BaseUrl
{
    public const SANDBOX = 'https://sandbox-api.bachs.io/v1';

    public const LIVE = 'https://api.bachs.io/v1';

    private const SANDBOX_HOST = 'https://sandbox-api.bachs.io';

    private const LIVE_HOST = 'https://api.bachs.io';

    /**
     * Resolve the base URL for a connection configuration.
     *
     * Precedence:
     *  1. An explicit `base_url`.
     *  2. The `env` value (`sandbox` | `live`), with the version segment from
     *     `api_version` (default `v1`).
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

        $version = self::version($config);
        $env = strtolower(trim((string) ($config['env'] ?? '')));
        $secret = (string) ($config['secret'] ?? '');

        if ($env === '') {
            return (str_starts_with($secret, 'sk_sandbox_') ? self::SANDBOX_HOST : self::LIVE_HOST).'/'.$version;
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

        return ($env === 'live' ? self::LIVE_HOST : self::SANDBOX_HOST).'/'.$version;
    }

    /**
     * The API version segment appended to the base host, e.g. `v1`.
     *
     * @param  array<string, mixed>  $config
     */
    private static function version(array $config): string
    {
        $version = trim((string) ($config['api_version'] ?? 'v1'), '/');

        return $version === '' ? 'v1' : $version;
    }

    private static function trim(string $url): string
    {
        return rtrim($url, '/');
    }
}
