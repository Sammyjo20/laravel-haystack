<?php

namespace Sammyjo20\LaravelJobStack\Actions;

use Illuminate\Queue\Events\JobProcessed;
use Sammyjo20\LaravelJobStack\Models\JobStack;

class ProcessCompletedJob
{
    /**
     * Constructor
     *
     * @param JobProcessed $jobProcessed
     */
    public function __construct(protected JobProcessed $jobProcessed)
    {
        //
    }

    /**
     * Attempt to find the job_stack_id on the processed job.
     *
     * @return void
     */
    public function execute(): void
    {
        $job = $this->jobProcessed->job;

        $jobStackId = $job->payload()['data']['job_stack_id'] ?? null;

        if (blank($jobStackId)) {
            return;
        }

        // Now we'll try to find the JobStack from the ID.

        $jobStack = JobStack::findOrFail($jobStackId);

        // Once we have found the JobStack, we'll check if the current job
        // has failed. If it has, then we'll fail the whole stack. Otherwise,
        // we will dispatch the next job.

        $job->hasFailed() ? $jobStack->fail() : $jobStack->dispatchNextJob();
    }
}
