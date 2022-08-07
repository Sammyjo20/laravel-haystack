<?php

namespace Sammyjo20\LaravelHaystack\Middleware;

use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Helpers\ExceptionHelper;

class CheckFinished
{
    /**
     * Stop processing job if the haystack has finished.
     *
     * @param StackableJob $job
     * @param $next
     * @return void
     */
    public function handle(StackableJob $job, $next): void
    {
        if ($job->getHaystack()->finished === true) {
             return;
        }

        $next($job);
    }
}
