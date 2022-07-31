<?php

namespace Sammyjo20\LaravelHaystack\Data;

use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class PendingHaystackBale
{
    /**
     * Constructor
     *
     * @param StackableJob $job
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     */
    public function __construct(
        readonly public StackableJob $job,
        readonly public int          $delayInSeconds = 0,
        readonly public ?string      $queue = null,
        readonly public ?string      $connection = null,
    )
    {
        //
    }
}
