<?php

namespace OkekeDev\Bachs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WebhookListCommand extends Command
{
    protected $signature = 'bachs:webhook:list
        {--limit=20 : Number of events to show}
        {--type= : Filter by event type}
        {--connection= : The database connection to use}';

    protected $description = 'List recent webhook events from the database';

    public function handle(): int
    {
        if (! Config::get('bachs.database.sync', false)) {
            $this->error('Database sync is not enabled. Set BACHS_DB_SYNC=true to use this command.');

            return self::FAILURE;
        }

        $table = Config::get('bachs.database.tables.webhook_events', 'bachs_webhook_events');
        $connection = Config::get('bachs.database.connection');

        $query = DB::connection($connection)
            ->table($table)
            ->orderByDesc('created_at');

        if ($this->option('type')) {
            $query->where('type', $this->option('type'));
        }

        $events = $query->limit($this->option('limit'))->get();

        if ($events->isEmpty()) {
            $this->info('No webhook events found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Event ID', 'Type', 'Organization', 'Processed At'],
            $events->map(fn ($e) => [
                $e->event_id,
                $e->type,
                $e->organization_id,
                $e->processed_at,
            ])
        );

        $this->info("Showing {$events->count()} event(s).");

        return self::SUCCESS;
    }
}
