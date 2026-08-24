<?php

namespace OkekeDev\Bachs\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'bachs:install
        {--force : Overwrite existing config and migration files}';

    protected $description = 'Publish Bachs config, migrations, and run migrations';

    public function handle(): int
    {
        $tag = 'bachs-config';

        // Publish config
        $this->call('vendor:publish', [
            '--tag' => $tag,
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--tag' => 'bachs-migrations',
            '--force' => $this->option('force'),
        ]);

        // Publish views
        $this->call('vendor:publish', [
            '--tag' => 'bachs-views',
            '--force' => $this->option('force'),
        ]);

        $this->info('Bachs config, migrations, and views published.');

        // Run migrations
        if ($this->confirm('Run migrations now?')) {
            $this->call('migrate');
            $this->info('Migrations complete.');
        }

        $this->info('Bachs installed successfully!');

        return self::SUCCESS;
    }
}
