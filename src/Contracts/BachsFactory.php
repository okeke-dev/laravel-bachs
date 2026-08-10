<?php

namespace OkekeDev\Bachs\Contracts;

use OkekeDev\Bachs\BachsClient;

interface BachsFactory
{
    /**
     * Get a connection by name, falling back to the default connection.
     */
    public function connection(?string $name = null): BachsClient;
}
