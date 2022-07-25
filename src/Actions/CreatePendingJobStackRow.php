<?php

namespace Sammyjo20\LaravelJobStack\Actions;

use Illuminate\Contracts\Queue\ShouldQueue;
use InvalidArgumentException;
use Sammyjo20\LaravelJobStack\Data\PendingJobStackRow;
use Sammyjo20\LaravelJobStack\Helpers\Stackable;

class CreatePendingJobStackRow
{
    /**
     * Create a new PendingJobStackRow.
     *
     * @param ShouldQueue $job
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return PendingJobStackRow
     */
    public static function execute(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): PendingJobStackRow
    {
        if (Stackable::isNotStackable($job)) {
            throw new InvalidArgumentException('The provided job does not contain the "Stackable" trait.');
        }

        return new PendingJobStackRow($job, $delayInSeconds, $queue, $connection);
    }
}
