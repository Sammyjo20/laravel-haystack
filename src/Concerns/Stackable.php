<?php

namespace Sammyjo20\LaravelJobStack\Concerns;

use Illuminate\Contracts\Queue\ShouldQueue;
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
     * Get the job stack.
     *
     * @return JobStack
     */
    public function getJobStack(): JobStack
    {
        return $this->jobStack;
    }

    /**
     * Set the JobStack onto the job.
     *
     * @param  JobStack  $jobStack
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
     * @return $this
     */
    public function nextJob(): static
    {
        $this->jobStack->dispatchNextJob();

        return $this;
    }

    /**
     * Finish the JobStack.
     *
     * @return $this
     */
    public function finishJobStack(): static
    {
        $this->jobStack->finish();

        return $this;
    }

    /**
     * Fail the job stack.
     *
     * @return $this
     */
    public function failJobStack(): static
    {
        $this->jobStack->finish(true);

        return $this;
    }

    /**
     * Append a job to the JobStack.
     *
     * @param  ShouldQueue  $job
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return $this
     */
    public function appendJob(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        $this->jobStack->appendJob($job, $delayInSeconds, $queue, $connection);

        return $this;
    }
}
