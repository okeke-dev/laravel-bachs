<?php

namespace OkekeDev\Bachs;

use Illuminate\Contracts\Container\Container;
use OkekeDev\Bachs\Contracts\BachsFactory;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Support\BaseUrl;

class BachsManager implements BachsFactory
{
    /**
     * Resolved connections, keyed by name.
     *
     * @var array<string, BachsClient>
     */
    protected array $connections = [];

    public function __construct(protected Container $app) {}

    /**
     * Get a connection by name, falling back to the default connection.
     */
    public function connection(?string $name = null): BachsClient
    {
        $name = $name ?: $this->getDefaultConnection();

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    /**
     * The name of the default connection.
     */
    protected function getDefaultConnection(): string
    {
        return (string) $this->config('default', 'default');
    }

    /**
     * Resolve a connection's configuration.
     *
     * @return array<string, mixed>
     */
    protected function configuration(string $name): array
    {
        $name = $name ?: $this->getDefaultConnection();

        $connections = $this->config('connections', []);

        if (! is_array($connections) || ! isset($connections[$name]) || ! is_array($connections[$name])) {
            throw new BachsInvalidArgumentException(sprintf('Bachs connection [%s] is not configured.', $name));
        }

        return $connections[$name];
    }

    /**
     * Build a BachsClient for the given connection.
     */
    protected function resolve(string $name): BachsClient
    {
        $config = $this->configuration($name);

        return new BachsClient(
            secret: (string) ($config['secret'] ?? ''),
            baseUrl: BaseUrl::resolve($config),
            config: $config,
        );
    }

    /**
     * Read a value from the package configuration.
     */
    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->app['config']->get("bachs.{$key}", $default);
    }

    /**
     * Forward unknown calls to the default connection.
     *
     * @param  array<mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
