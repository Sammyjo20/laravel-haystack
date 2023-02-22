<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Middleware;

use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class CheckFinished
{
    /**
     * Stop processing job if the haystack has finished.
     */
    public function handle(StackableJob $job, $next): void
    {
        if ($job->getHaystack()->finished === true) {
            return;
        }

        $next($job);
    }
}
