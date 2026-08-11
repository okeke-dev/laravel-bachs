<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\BachsClient;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;

/**
 * Base class for Bachs resources.
 *
 * Each resource (e.g. `Products`) exposes static entry points such as
 * `Products::create(...)` that run through the default connection's client,
 * which the service provider seeds at boot.
 *
 * Subclasses must not redeclare the `$defaultClient` static property so that
 * every resource shares the single default client.
 */
abstract class BachsResource
{
    /**
     * The client used by resource calls (the default connection).
     */
    protected static ?BachsClient $defaultClient = null;

    /**
     * Set the client used by resource calls.
     */
    public static function setDefaultClient(?BachsClient $client): void
    {
        static::$defaultClient = $client;
    }

    /**
     * The client used by resource calls.
     */
    public static function defaultClient(): BachsClient
    {
        if (static::$defaultClient === null) {
            throw new BachsInvalidArgumentException(
                'No default Bachs client is configured. Register the Bachs service provider and set a secret key before calling resource methods.'
            );
        }

        return static::$defaultClient;
    }
}
