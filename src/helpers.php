<?php

use OkekeDev\Bachs\BachsClient;
use OkekeDev\Bachs\Contracts\BachsFactory;

if (! function_exists('bachs')) {
    /**
     * Get the Bachs client instance, optionally for a named connection.
     *
     * @param  string|null  $connection  The connection name (null for the default).
     */
    function bachs(?string $connection = null): BachsClient
    {
        /** @var BachsFactory $factory */
        $factory = app(BachsFactory::class);

        return $factory->connection($connection);
    }
}
