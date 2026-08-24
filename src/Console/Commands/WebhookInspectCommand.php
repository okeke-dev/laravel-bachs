<?php

namespace OkekeDev\Bachs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WebhookInspectCommand extends Command
{
    protected $signature = 'bachs:webhook:inspect
        {event_id : The event ID to inspect}
        {--connection= : The database connection to use}';

    protected $description = 'Inspect a specific webhook event from the database';

    public function handle(): int
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

        $this->info('Webhook Event Details:');
        $this->line('');
        $this->line("  Event ID:       {$event->event_id}");
        $this->line("  Type:           {$event->type}");
        $this->line("  Organization:   {$event->organization_id}");
        $this->line('  Account:        '.($event->account ?? 'N/A'));
        $this->line("  Created At:     {$event->event_created_at}");
        $this->line("  Processed At:   {$event->processed_at}");
        $this->line('');

        $data = json_decode($event->data, true);
        if ($data !== null) {
            $this->info('  Payload:');
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
