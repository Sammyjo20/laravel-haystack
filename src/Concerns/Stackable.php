<?php

namespace Sammyjo20\LaravelJobStack\Concerns;

use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelJobStack\Actions\CreatePendingJobStackRow;
use Sammyjo20\LaravelJobStack\Models\JobStack;

trait Stackable
{
    /**
     * The JobStack the job has.
     *
     * @var JobStack
     */
    protected JobStack $jobStack;

    /**
     * Set the JobStack onto the job.
     *
     * @param JobStack $jobStack
     * @return $this
     */
    public function setJobStack(JobStack $jobStack): static
    {
        $this->jobStack = $jobStack;

        return $this;
    }

    /**
     * Dispatch the next job in the JobStack.
     *
     * @return void
     */
    public function nextJob(): void
    {
        $this->jobStack->dispatchNextJob();
    }

    /**
     * Finish the JobStack.
     *
     * @return void
     */
    public function finishJobStack(): void
    {
        $this->jobStack->finish();
    }

    /**
     * Fail the job stack.
     *
     * @return void
     */
    public function failJobStack(): void
    {
        $this->jobStack->finish(true);
    }

    /**
     * Append a job to the JobStack.
     *
     * @param ShouldQueue $job
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return void
     */
    public function appendJob(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): void
    {
        $this->jobStack->appendJob($job, $delayInSeconds, $queue, $connection);
    }
}
