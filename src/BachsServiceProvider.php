<?php

namespace OkekeDev\Bachs;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use OkekeDev\Bachs\Contracts\BachsFactory;

class BachsServiceProvider extends ServiceProvider
{
    /**
     * Register the package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bachs.php', 'bachs');

        $this->registerManager();
    }

    /**
     * Bootstrap the package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bachs.php' => $this->app->configPath('bachs.php'),
            ], 'bachs-config');
        }
    }

    /**
     * Bind the Bachs manager into the container.
     */
    protected function registerManager(): void
    {
        $this->app->singleton(BachsFactory::class, function (Container $app): BachsManager {
            return new BachsManager($app);
        });

        $this->app->alias(BachsFactory::class, 'bachs');
    }
}
