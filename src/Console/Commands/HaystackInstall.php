<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Console\Commands;

use Illuminate\Console\Command;

class HaystackInstall extends Command
{
    /**
     * @var string
     */
    public $signature = 'haystack:install';

    /**
     * @var string
     */
    public $description = 'Install Laravel Haystack';

    /**
     * Install Haystack
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Publishing migrations...');

        $this->call('vendor:publish', ['--tag' => 'haystack-migrations']);

        $this->info('Publishing config...');

        $this->call('vendor:publish', ['--tag' => 'haystack-config']);

        $runMigrations = $this->confirm('Would you like to run migrations?', false);

        if ($runMigrations) {
            $this->call('migrate');
        }g

        // Todo: Add star on Github?

        return self::SUCCESS;
    }
}
