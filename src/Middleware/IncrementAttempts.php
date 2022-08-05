<?php

namespace Sammyjo20\LaravelHaystack\Middleware;

use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class IncrementAttempts
{
    /**
     * Increment the processed attempts of a given job.
     *
     * @param  StackableJob  $job
     * @param $next
     * @return void
     */
    public function handle(StackableJob $job, $next): void
    {
        $job->getHaystack()->incrementBaleAttempts($job);

        $next($job);
    }
}
