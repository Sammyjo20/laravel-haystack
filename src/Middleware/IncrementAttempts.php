<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Middleware;

use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class IncrementAttempts
{
    /**
     * Increment the processed attempts of a given job.
     */
    public function handle(StackableJob $job, $next): void
    {
        $job->getHaystack()->incrementBaleAttempts($job);

        $next($job);
    }
}
