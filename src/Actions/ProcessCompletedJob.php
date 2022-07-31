<?php

namespace Sammyjo20\LaravelHaystack\Actions;

use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class ProcessCompletedJob
{
    /**
     * Constructor
     *
     * @param  JobProcessed  $jobProcessed
     */
    public function __construct(protected JobProcessed $jobProcessed)
    {
        //
    }

    /**
     * Attempt to find the haystack_id on the processed job.
     *
     * @return void
     */
    public function execute(): void
    {
        $processedJob = $this->jobProcessed->job;
        $payload = $processedJob->payload();

        // We now need to unserialize the real job under the hood, to check if it has
        // been delayed.

        $job = isset($payload['data']['command']) ? unserialize($payload['data']['command'], ['allowed_classes' => true]) : null;

        if (! $job instanceof StackableJob) {
            return;
        }

        // If the job has been pushed back onto the queue, we will wait.
        // We will ignore this for sync jobs since they never stop processing.

        if ($processedJob instanceof SyncJob === false && $processedJob->isReleased() === true && $processedJob->hasFailed() === false) {
            return;
        }

        // Otherwise, we'll attempt to find the haystack_id added to the jobs
        // and retrieve it.

        $haystackId = $payload['data']['haystack_id'] ?? null;

        if (blank($haystackId)) {
            return;
        }

        // Now we'll try to find the Haystack from the ID.

        $haystack = Haystack::find($haystackId);

        if (! $haystack instanceof Haystack) {
            return;
        }

        // Once we have found the Haystack, we'll check if the current job
        // has failed. If it has, then we'll fail the whole stack. Otherwise,
        // we will dispatch the next job.

        $processedJob->hasFailed() ? $haystack->fail() : $haystack->dispatchNextJob($job);
    }
}
