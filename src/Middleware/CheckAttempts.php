<?php

namespace Sammyjo20\LaravelHaystack\Middleware;

use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Helpers\ExceptionHelper;

class CheckAttempts
{
    /**
     * Check if we have exceeded the attempts.
     *
     * @param  StackableJob  $job
     * @param $next
     * @return void
     */
    public function handle(StackableJob $job, $next): void
    {
        $maxTries = $job->tries ?? 1;

        if ($job->getHaystackBaleAttempts() > $maxTries) {
            $job->fail(ExceptionHelper::maxAttemptsExceededException($job));
            return;
        }

        $next($job);
    }
}
