<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Config::set('bachs.logging.enabled', false);
    Config::set('bachs.database.sync', false);

    // Create webhook_events table for persistence tests
    Schema::create('bachs_webhook_events', function ($table) {
        $table->id();
        $table->string('event_id')->unique();
        $table->string('type');
        $table->string('organization_id');
        $table->string('account')->nullable();
        $table->text('data');
        $table->string('event_created_at');
        $table->string('processed_at');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('bachs_webhook_events');
});

it('registers the install command', function () {
    $this->artisan('bachs:install', ['--force' => true])
        ->expectsConfirmation('Run migrations now?', 'no')
        ->assertExitCode(0);
});

it('registers the health command and checks connectivity', function () {
    Config::set('bachs.connections.default.secret', 'sk_sandbox_test_key');
    Config::set('bachs.connections.default.env', 'sandbox');

    Http::fake([
        'sandbox-api.bachs.io/v1/products*' => Http::response(['data' => []], 200),
    ]);

    $this->artisan('bachs:health')
        ->assertExitCode(0);
});

it('health command fails without secret', function () {
    Config::set('bachs.connections.default.secret', null);

    $this->artisan('bachs:health')
        ->assertExitCode(1);
});

it('health command fails with unreachable API', function () {
    Config::set('bachs.connections.default.secret', 'sk_sandbox_test_key');
    Config::set('bachs.connections.default.env', 'sandbox');

    Http::fake(function () {
        return Http::response(['error' => 'Unauthorized'], 401);
    });

    $this->artisan('bachs:health')
        ->assertExitCode(1);
});

it('webhook:test command processes event without persistence', function () {
    Config::set('bachs.database.sync', false);

    $this->artisan('bachs:webhook:test', ['type' => 'collection.succeeded'])
        ->assertExitCode(0);
});

it('webhook:test command persists event when sync is enabled', function () {
    Config::set('bachs.database.sync', true);

    $this->artisan('bachs:webhook:test', ['type' => 'customer.created'])
        ->assertExitCode(0);

    $events = DB::table('bachs_webhook_events')
        ->where('type', 'customer.created')
        ->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->event_id)->toContain('evt_test_');
});

it('webhook:list command fails when sync is disabled', function () {
    Config::set('bachs.database.sync', false);

    $this->artisan('bachs:webhook:list')
        ->assertExitCode(1);
});

it('webhook:list command shows events', function () {
    Config::set('bachs.database.sync', true);

    DB::table('bachs_webhook_events')->insert([
        'event_id' => 'evt_list_1',
        'type' => 'collection.succeeded',
        'organization_id' => 'org_1',
        'data' => json_encode([]),
        'event_created_at' => now()->toIso8601String(),
        'processed_at' => now()->toIso8601String(),
    ]);

    $this->artisan('bachs:webhook:list')
        ->assertExitCode(0);
});

it('webhook:list command filters by type', function () {
    Config::set('bachs.database.sync', true);

    DB::table('bachs_webhook_events')->insert([
        'event_id' => 'evt_list_2',
        'type' => 'collection.succeeded',
        'organization_id' => 'org_1',
        'data' => json_encode([]),
        'event_created_at' => now()->toIso8601String(),
        'processed_at' => now()->toIso8601String(),
    ]);

    $this->artisan('bachs:webhook:list', ['--type' => 'collection.succeeded'])
        ->assertExitCode(0);
});

it('webhook:inspect command fails when sync is disabled', function () {
    Config::set('bachs.database.sync', false);

    $this->artisan('bachs:webhook:inspect', ['event_id' => 'evt_123'])
        ->assertExitCode(1);
});

it('webhook:inspect command fails for missing event', function () {
    Config::set('bachs.database.sync', true);

    $this->artisan('bachs:webhook:inspect', ['event_id' => 'evt_nonexistent'])
        ->assertExitCode(1);
});

it('webhook:inspect command shows event details', function () {
    Config::set('bachs.database.sync', true);

    DB::table('bachs_webhook_events')->insert([
        'event_id' => 'evt_inspect_1',
        'type' => 'collection.succeeded',
        'organization_id' => 'org_1',
        'data' => json_encode(['payment_id' => 'pay_123']),
        'event_created_at' => '2026-01-15T10:00:00Z',
        'processed_at' => '2026-01-15T10:00:01Z',
    ]);

    $this->artisan('bachs:webhook:inspect', ['event_id' => 'evt_inspect_1'])
        ->assertExitCode(0);
});

it('webhook:replay command fails when sync is disabled', function () {
    Config::set('bachs.database.sync', false);

    $this->artisan('bachs:webhook:replay', ['event_id' => 'evt_123'])
        ->assertExitCode(1);
});

it('webhook:replay command fails for missing event', function () {
    Config::set('bachs.database.sync', true);

    $this->artisan('bachs:webhook:replay', ['event_id' => 'evt_nonexistent'])
        ->assertExitCode(1);
});

it('webhook:replay command replays a stored event', function () {
    Config::set('bachs.database.sync', true);

    DB::table('bachs_webhook_events')->insert([
        'event_id' => 'evt_replay_1',
        'type' => 'customer.created',
        'organization_id' => 'org_1',
        'data' => json_encode([
            'customer_id' => 'cus_replay',
            'email' => 'replay@example.com',
        ]),
        'event_created_at' => now()->toIso8601String(),
        'processed_at' => now()->toIso8601String(),
    ]);

    $this->artisan('bachs:webhook:replay', ['event_id' => 'evt_replay_1', '--force' => true])
        ->assertExitCode(0);
});

it('all artisan commands are registered', function () {
    $commands = [
        'bachs:install',
        'bachs:health',
        'bachs:webhook:test',
        'bachs:webhook:list',
        'bachs:webhook:inspect',
        'bachs:webhook:replay',
    ];

    foreach ($commands as $command) {
        $this->artisan($command, ['--help' => true])
            ->assertExitCode(0);
    }
});
