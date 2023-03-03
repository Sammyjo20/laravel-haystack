<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Helpers;

use Throwable;
use Illuminate\Queue\MaxAttemptsExceededException;

class ExceptionHelper
{
    /**
     * Get the max attempts exceeded exception.
     */
    public static function maxAttemptsExceededException($job): Throwable
    {
        return new MaxAttemptsExceededException(
            $job::class.' has been attempted too many times or run too long. The job may have previously timed out.'
        );
    }
}
