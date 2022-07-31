<?php

namespace Sammyjo20\LaravelHaystack\Actions;

use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;

class CreatePendingHaystackBale
{
    /**
     * Create a new PendingHaystackRow.
     *
     * @param  StackableJob  $job
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return PendingHaystackBale
     */
    public static function execute(StackableJob $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): PendingHaystackBale
    {
        return new PendingHaystackBale($job, $delayInSeconds, $queue, $connection);
    }
}
