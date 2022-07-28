<?php

namespace Sammyjo20\LaravelHaystack\Actions;

use Illuminate\Contracts\Queue\ShouldQueue;
use InvalidArgumentException;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Helpers\Stackable;

class CreatePendingHaystackBale
{
    /**
     * Create a new PendingHaystackRow.
     *
     * @param  ShouldQueue  $job
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return PendingHaystackBale
     */
    public static function execute(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): PendingHaystackBale
    {
        if (Stackable::isNotStackable($job)) {
            throw new InvalidArgumentException('The provided job does not contain the "Stackable" trait.');
        }

        return new PendingHaystackBale($job, $delayInSeconds, $queue, $connection);
    }
}