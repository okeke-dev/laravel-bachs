<?php

namespace OkekeDev\Bachs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OkekeDev\Bachs\Webhooks\WebhookEvent;
use OkekeDev\Bachs\Webhooks\WebhookProcessor;

class WebhookTestCommand extends Command
{
    protected $signature = 'bachs:webhook:test
        {type? : Event type to simulate (default: collection.succeeded)}
        {--connection= : The Bachs connection to use}';

    protected $description = 'Simulate a webhook event delivery for testing';

    public function handle(): int
    {
        $type = $this->argument('type') ?? 'collection.succeeded';
        $eventId = 'evt_test_'.Str::random(16);
        $now = now()->toIso8601String();

        $payload = [
            'id' => $eventId,
            'type' => $type,
            'created_at' => $now,
            'organization_id' => 'org_test',
            'data' => $this->buildSampleData($type),
        ];

        $this->line('Simulating webhook event:');
        $this->line("  Event ID:  {$eventId}");
        $this->line("  Type:      {$type}");
        $this->line('');

        // Store the event if persistence is enabled
        if (Config::get('bachs.database.sync', false)) {
            $table = Config::get('bachs.database.tables.webhook_events', 'bachs_webhook_events');
            $connection = Config::get('bachs.database.connection');

            DB::connection($connection)
                ->table($table)
                ->insert([
                    'event_id' => $eventId,
                    'type' => $type,
                    'organization_id' => 'org_test',
                    'account' => null,
                    'data' => json_encode($payload['data']),
                    'event_created_at' => $now,
                    'processed_at' => $now,
                ]);

            $this->info('  Persisted to database.');
        }

        // Dispatch the event through the processor
        $event = WebhookEvent::fromPayload($payload);
        $processor = app(WebhookProcessor::class);
        $processor->process($event);

        $this->info('  Event processed successfully.');
        $this->info('Webhook test complete.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSampleData(string $type): array
    {
        return match ($type) {
            'collection.succeeded' => [
                'payment_id' => 'pay_test_'.Str::random(8),
                'status' => 'succeeded',
                'amount' => '29.99',
                'currency' => 'USD',
            ],
            'collection.failed' => [
                'payment_id' => 'pay_test_'.Str::random(8),
                'status' => 'failed',
                'amount' => '29.99',
                'currency' => 'USD',
            ],
            'customer.created' => [
                'customer_id' => 'cus_test_'.Str::random(8),
                'email' => 'test@example.com',
                'name' => 'Test Customer',
            ],
            'customer.subscription.created' => [
                'subscription_id' => 'sub_test_'.Str::random(8),
                'customer_id' => 'cus_test_'.Str::random(8),
                'status' => 'active',
                'amount' => '29.99',
                'currency' => 'USD',
            ],
            'checkout.completed' => [
                'checkout_id' => 'chk_test_'.Str::random(8),
                'status' => 'COMPLETE',
                'payment_status' => 'paid',
            ],
            default => [
                'message' => 'Test event payload',
            ],
        };
    }
}
