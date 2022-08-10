<?php

namespace Sammyjo20\LaravelHaystack\Console\Commands;

use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class HaystacksForget extends Command
{
    public $signature = 'haystacks:forget {id}';

    public $description = 'Delete a haystack by ID.';

    public function handle(): int
    {
        $haystack = Haystack::find($this->argument('id'));

        if (! $haystack) {
            $this->error('No haystack matches the given ID.');

            return self::FAILURE;
        }

        $haystack->delete();
        $this->info('Haystack deleted successfully!');

        return self::SUCCESS;
    }
}
