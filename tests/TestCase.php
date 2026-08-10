<?php

namespace OkekeDev\Bachs\Tests;

use Illuminate\Foundation\Application;
use OkekeDev\Bachs\BachsServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get the package service providers.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BachsServiceProvider::class,
        ];
    }

    /**
     * Define the package configuration for tests.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('bachs.connections.default.secret', 'sk_sandbox_test_secret');
        $app['config']->set('bachs.connections.default.env', 'sandbox');
    }
}
