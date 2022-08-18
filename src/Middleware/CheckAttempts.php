<?php

declare(strict_types=1);

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
     *
     * @throws \Throwable
     */
    public function handle(StackableJob $job, $next): void
    {
        $maxTries = $job->tries ?? 1;

        if ($job->getHaystackBaleAttempts() >= $maxTries) {
            $exception = ExceptionHelper::maxAttemptsExceededException($job);

            $job->fail($exception);

            throw $exception;
        }

        $next($job);
    }
}
