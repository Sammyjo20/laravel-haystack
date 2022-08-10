<?php

namespace Sammyjo20\LaravelHaystack\Console\Commands;

use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class HaystacksClear extends Command
{
    public $signature = 'haystacks:clear';

    public $description = 'Delete all haystacks.';

    public function handle(): int
    {
        $count = Haystack::query()->delete();

        $this->info("Cleared $count haystacks'");

        return self::SUCCESS;
    }
}
