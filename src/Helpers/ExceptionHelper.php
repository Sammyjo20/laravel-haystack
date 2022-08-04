<?php

namespace Sammyjo20\LaravelHaystack\Helpers;

use Illuminate\Queue\MaxAttemptsExceededException;
use Throwable;

class ExceptionHelper
{
    /**
     * Get the max attempts exceeded exception.
     *
     * @param $job
     * @return Throwable
     */
    public static function maxAttemptsExceededException($job): Throwable
    {
        return new MaxAttemptsExceededException(
            $job::class. ' has been attempted too many times or run too long. The job may have previously timed out.'
        );
    }
}
