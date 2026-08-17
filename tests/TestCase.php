<?php

namespace OkekeDev\Bachs\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
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

    /**
     * Define the database schema for tests.
     */
    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('bachs_customer_id')->nullable();
            $table->timestamps();
        });
    }
}
