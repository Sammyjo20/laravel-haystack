<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Data;

use Carbon\Carbon;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class PendingHaystackBale
{
    /**
     * Constructor
     */
    public function __construct(
        public StackableJob $job,
        public int $delayInSeconds = 0,
        public ?string $queue = null,
        public ?string $connection = null,
        public bool $priority = false,
    ) {
        $nativeDelay = $this->job->delay;
        $nativeQueue = $this->job->queue;
        $nativeConnection = $this->job->connection;

        if (isset($nativeDelay) && $this->delayInSeconds <= 0) {
            $this->delayInSeconds = $nativeDelay;
        }

        if (isset($nativeQueue) && ! isset($this->queue)) {
            $this->queue = $nativeQueue;
        }

        if (isset($nativeConnection) && ! isset($this->connection)) {
            $this->connection = $nativeConnection;
        }
    }

    /**
     * Convert to a haystack bale for casting.
     */
    public function toDatabaseRow(Haystack $haystack): array
    {
        $now = Carbon::now();

        return $haystack->bales()->make([
            'created_at' => $now,
            'updated_at' => $now,
            'job' => $this->job,
            'delay' => $this->delayInSeconds,
            'on_queue' => $this->queue,
            'on_connection' => $this->connection,
            'priority' => $this->priority,
        ])->getAttributes();
    }
}
