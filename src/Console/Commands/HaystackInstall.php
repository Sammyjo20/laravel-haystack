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
     */
    public function handle(): int
    {
        $this->info(' 🚀 | Installing Haystack');

        $this->info(' 🪐 | Publishing migrations...');

        $this->callSilently('vendor:publish', ['--tag' => 'haystack-migrations']);

        $this->info(' 🔭 | Publishing config...');

        $this->callSilently('vendor:publish', ['--tag' => 'haystack-config']);

        $runMigrations = $this->confirm('Would you like to run migrations?', false);

        if ($runMigrations) {
            $this->callSilently('migrate');

            $this->info(' 🎯 | Migrations run successfully');
        }

        if ($this->confirm(' 🤩 | Would you like to star the repo on GitHub?')) {
            $repoUrl = 'https://github.com/sammyjo20/laravel-haystack';

            if (PHP_OS_FAMILY == 'Darwin') {
                exec("open {$repoUrl}");
            }

            if (PHP_OS_FAMILY == 'Windows') {
                exec("start {$repoUrl}");
            }

            if (PHP_OS_FAMILY == 'Linux') {
                exec("xdg-open {$repoUrl}");
            }
        }

        $this->info(' ✅ | Haystack has been installed. Thank you for using Haystack. Happy developing! ❤️');

        return self::SUCCESS;
    }
}
