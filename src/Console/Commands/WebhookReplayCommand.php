<?php

namespace OkekeDev\Bachs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use OkekeDev\Bachs\Webhooks\WebhookEvent;
use OkekeDev\Bachs\Webhooks\WebhookProcessor;

class WebhookReplayCommand extends Command
{
    protected $signature = 'bachs:webhook:replay
        {event_id : The event ID to replay}
        {--connection= : The database connection to use}
        {--force : Replay even if already processed}';

    protected $description = 'Replay a previously received webhook event';

    public function handle(WebhookProcessor $processor): int
    {
        if (! Config::get('bachs.database.sync', false)) {
            $this->error('Database sync is not enabled. Set BACHS_DB_SYNC=true to use this command.');

            return self::FAILURE;
        }

        $table = Config::get('bachs.database.tables.webhook_events', 'bachs_webhook_events');
        $connection = Config::get('bachs.database.connection');
        $eventId = $this->argument('event_id');

        $event = DB::connection($connection)
            ->table($table)
            ->where('event_id', $eventId)
            ->first();

        if ($event === null) {
            $this->error("Event '{$eventId}' not found.");

            return self::FAILURE;
        }

        // Reconstruct the WebhookEvent from stored data
        $payload = [
            'id' => $event->event_id,
            'type' => $event->type,
            'created_at' => $event->event_created_at,
            'organization_id' => $event->organization_id,
            'account' => $event->account,
            'data' => json_decode($event->data, true) ?? [],
        ];

        $webhookEvent = WebhookEvent::fromPayload($payload);

        $this->line('Replaying webhook event:');
        $this->line("  Event ID: {$event->event_id}");
        $this->line("  Type:     {$event->type}");
        $this->line('');

        // If --force, delete the existing record first so the processor won't skip it
        if ($this->option('force')) {
            DB::connection($connection)
                ->table($table)
                ->where('event_id', $eventId)
                ->delete();

            $this->info('  Deleted existing event record (force mode).');
        }

        try {
            $processor->process($webhookEvent);
            $this->info('  Event replayed successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("  Failed to replay event: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
