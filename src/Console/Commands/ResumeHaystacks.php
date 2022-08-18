<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Console\Commands;

use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class ResumeHaystacks extends Command
{
    public $signature = 'haystacks:resume';

    public $description = 'Resume any paused haystacks if it has reached the time. Should be executed every minute.';

    public function handle(): int
    {
        $haystacks = Haystack::query()->where('resume_at', '<=', now())->cursor();

        foreach ($haystacks as $haystack) {
            $haystack->update(['resume_at' => null]);
            $haystack->dispatchNextJob();
        }

        return self::SUCCESS;
    }
}
