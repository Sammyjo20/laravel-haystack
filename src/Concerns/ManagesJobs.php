<?php

namespace Sammyjo20\LaravelJobStack\Concerns;

use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelJobStack\Actions\CreatePendingJobStackRow;
use Sammyjo20\LaravelJobStack\Data\NextJob;
use Sammyjo20\LaravelJobStack\Data\PendingJobStackRow;
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
            $this->finish();

            return;
        }

        $nextJob->jobStackRow->delete();

        dispatch($nextJob->job);
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
     * @param  bool  $fail
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

        // Now finally delete itself.

        $this->delete();
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
     * Append a new job to the job stack.
     *
     * @param  ShouldQueue  $job
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return void
     */
    public function appendJob(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): void
    {
        $pendingJob = CreatePendingJobStackRow::execute($job, $delayInSeconds, $queue, $connection);

        $this->appendPendingJob($pendingJob);
    }

    /**
     * Append the pending job to the JobStack.
     *
     * @param  PendingJobStackRow  $pendingJob
     * @return void
     */
    public function appendPendingJob(PendingJobStackRow $pendingJob): void
    {
        $this->rows()->create([
            'job' => $pendingJob->job,
            'delay' => $pendingJob->delayInSeconds,
            'on_queue' => $pendingJob->queue,
            'on_connection' => $pendingJob->connection,
        ]);
    }

    /**
     * Execute the closure.
     *
     * @param  Closure|null  $closure
     * @return void
     */
    protected function executeClosure(?Closure $closure): void
    {
        if ($closure instanceof Closure) {
            $closure();
        }
    }
}
