<?php

namespace Sammyjo20\LaravelHaystack\Data;

use Illuminate\Contracts\Queue\ShouldQueue;

class PendingHaystackRow
{
    /**
     * Constructor
     *
     * @param  ShouldQueue  $job
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     */
    public function __construct(
        readonly public ShouldQueue $job,
        readonly public int $delayInSeconds = 0,
        readonly public ?string $queue = null,
        readonly public ?string $connection = null,
    ) {
        //
    }
}
