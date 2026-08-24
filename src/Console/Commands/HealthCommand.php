<?php

namespace OkekeDev\Bachs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class HealthCommand extends Command
{
    protected $signature = 'bachs:health
        {--connection= : The Bachs connection to check}';

    protected $description = 'Check Bachs API connectivity and configuration';

    public function handle(): int
    {
        $connection = $this->option('connection') ?: config('bachs.default', 'default');
        $config = config("bachs.connections.{$connection}");

        if ($config === null) {
            $this->error("Connection '{$connection}' is not configured.");

            return self::FAILURE;
        }

        $secret = $config['secret'] ?? null;

        if ($secret === null || $secret === '') {
            $this->error("No secret key configured for connection '{$connection}'.");

            return self::FAILURE;
        }

        $this->info("Checking Bachs API for connection '{$connection}'...");

        // Determine the base URL
        $env = $config['env'] ?? 'sandbox';
        $baseUrl = $config['base_url'] ?? $this->resolveBaseUrl($env, $secret);

        $this->line("  Environment: {$env}");
        $this->line("  Base URL:    {$baseUrl}");

        // Make a simple API request to check connectivity
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$secret}",
            'Accept' => 'application/json',
        ])->timeout(10)->get($baseUrl.'/v1/products?limit=1');

        if ($response->successful()) {
            $this->info('  Status:      Connected');
            $this->info('Bachs API is reachable.');

            return self::SUCCESS;
        }

        $this->error("  Status:      HTTP {$response->status()}");
        $this->error('Bachs API is not reachable.');

        return self::FAILURE;
    }

    protected function resolveBaseUrl(string $env, string $secret): string
    {
        if (str_starts_with($secret, 'sk_live_')) {
            return 'https://api.bachs.io';
        }

        return 'https://sandbox-api.bachs.io';
    }
}
