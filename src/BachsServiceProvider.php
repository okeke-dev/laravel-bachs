<?php

namespace OkekeDev\Bachs;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use OkekeDev\Bachs\Contracts\BachsFactory;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Http\Controllers\WebhookController;
use OkekeDev\Bachs\Resources\BachsResource;

class BachsServiceProvider extends ServiceProvider
{
    /**
     * Register the package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bachs.php', 'bachs');

        $this->registerManager();
        $this->registerModels();
    }

    /**
     * Bootstrap the package services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerBladeComponents();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bachs.php' => $this->app->configPath('bachs.php'),
            ], 'bachs-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'bachs-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/bachs'),
            ], 'bachs-views');

            $this->registerCommands();
        }

        $this->registerDefaultResourceClient();
    }

    /**
     * Register the package view namespace.
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'bachs');
    }

    /**
     * Register Blade components.
     */
    protected function registerBladeComponents(): void
    {
        $this->loadViewComponentsAs('bachs', [
            'checkout' => View\Components\Checkout::class,
            'checkout-overlay' => View\Components\CheckoutOverlay::class,
            'subscribe' => View\Components\Subscribe::class,
        ]);
    }

    /**
     * Register the package Artisan commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            Console\Commands\InstallCommand::class,
            Console\Commands\HealthCommand::class,
            Console\Commands\WebhookTestCommand::class,
            Console\Commands\WebhookListCommand::class,
            Console\Commands\WebhookInspectCommand::class,
            Console\Commands\WebhookReplayCommand::class,
        ]);
    }

    /**
     * Seed static resource calls (`Products::create(...)`) with the default
     * connection's client. If Bachs is not configured yet, resource calls will
     * raise a helpful error when they run instead of breaking application boot.
     */
    protected function registerDefaultResourceClient(): void
    {
        try {
            BachsResource::setDefaultClient($this->app->make(BachsFactory::class)->connection());
        } catch (BachsInvalidArgumentException) {
            // Bachs is not configured; leave the default client unset.
        }
    }

    /**
     * Register the model classes as singletons for easy resolution.
     */
    protected function registerModels(): void
    {
        $models = [
            'bachs.customer' => Models\BachsCustomer::class,
            'bachs.product' => Models\BachsProduct::class,
            'bachs.payment' => Models\BachsPayment::class,
            'bachs.subscription' => Models\BachsSubscription::class,
        ];

        foreach ($models as $abstract => $concrete) {
            $this->app->singleton($abstract, $concrete);
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

    /**
     * Register the webhook route.
     */
    protected function registerRoutes(): void
    {
        $path = config('bachs.webhook.path', 'bachs/webhook');
        $middleware = config('bachs.webhook.middleware', []);

        $this->app['router']->post($path, WebhookController::class)
            ->middleware($middleware)
            ->name('bachs.webhook');
    }
}
