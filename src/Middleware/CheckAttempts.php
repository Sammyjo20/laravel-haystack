<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Middleware;

use Carbon\CarbonImmutable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Helpers\ExceptionHelper;

class CheckAttempts
{
    /**
     * Check if we have exceeded the attempts.
     *
     *
     * @throws \Throwable
     */
    public function handle(StackableJob $job, $next): void
    {
        $exceededRetryUntil = false;
        $maxTries = null;

        if (is_int($job->getHaystackBaleRetryUntil())) {
            $exceededRetryUntil = now()->greaterThan(CarbonImmutable::parse($job->getHaystackBaleRetryUntil()));
        } else {
            $maxTries = $job->tries ?? 1;
        }

        $exceededLimit = (isset($maxTries) && $job->getHaystackBaleAttempts() >= $maxTries) || $exceededRetryUntil === true;

        if ($exceededLimit === true) {
            $exception = ExceptionHelper::maxAttemptsExceededException($job);

            $job->fail($exception);

            throw $exception;
        }

        $next($job);
    }
}
