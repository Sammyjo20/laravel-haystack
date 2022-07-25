<?php

namespace Sammyjo20\LaravelJobStack\Commands;

use Illuminate\Console\Command;

class LaravelJobStackCommand extends Command
{
    public $signature = 'laravel-job-stack';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
