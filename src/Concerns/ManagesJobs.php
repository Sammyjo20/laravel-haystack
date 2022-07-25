<?php

namespace Sammyjo20\LaravelJobStack\Concerns;

use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelJobStack\Data\NextJob;
use Sammyjo20\LaravelJobStack\Models\JobStackRow;

trait ManagesJobs
{
    /**
     * Get the next job row in the JobStack.
     *
     * @return JobStackRow|null
     */
    public function getNextJobRow(): ?JobStackRow
    {
        return $this->rows()->first();
    }

    /**
     * Get the next job from the JobStack.
     *
     * @return NextJob|null
     */
    public function getNextJob(): ?NextJob
    {
        $jobRow = $this->getNextJobRow();

        if (is_null($jobRow)) {
            return null;
        }

        $job = $jobRow->job;
        $job->setJobStack($this);

        if ($jobRow->delay > 0) {
            $job->delay($jobRow->delay);
        }

        if (filled($jobRow->queue)) {
            $job->onQueue($jobRow->on_queue);
        }

        if (filled($jobRow->connection)) {
            $job->onConnection($jobRow->on_connection);
        }

        return new NextJob($job, $jobRow);
    }

    /**
     * Dispatch the next job.
     *
     * @return void
     */
    public function dispatchNextJob(): void
    {
        $nextJob = $this->getNextJob();

        if (! $nextJob instanceof NextJob) {
            return;
        }

        dispatch($nextJob->job);

        $nextJob->jobStackRow->delete();
    }

    /**
     * Start the JobStack.
     *
     * @return void
     */
    public function start(): void
    {
        $this->update(['started' => true]);

        $this->dispatchNextJob();
    }

    /**
     * Finish the JobStack.
     *
     * @param bool $fail
     * @return void
     */
    public function finish(bool $fail = false): void
    {
        if ($this->finished === true) {
            return;
        }

        $this->update(['finished' => true]);

        $fail === true
            ? $this->executeClosure($this->on_catch)
            : $this->executeClosure($this->on_then);

        // Always execute the finally closure.

        $this->executeClosure($this->on_finally);
    }

    /**
     * Fail the JobStack.
     *
     * @return void
     */
    public function fail(): void
    {
        $this->finish(true);
    }

    /**
     * Execute the closure.
     *
     * @param Closure|null $closure
     * @return void
     */
    protected function executeClosure(?Closure $closure): void
    {
        if ($closure instanceof Closure) {
            $closure();
        }
    }
}
