<?php

namespace Sammyjo20\LaravelHaystack\Concerns;

use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelHaystack\Actions\CreatePendingHaystackBale;
use Sammyjo20\LaravelHaystack\Data\NextJob;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;

trait ManagesBales
{
    /**
     * Get the next job row in the Haystack.
     *
     * @return HaystackBale|null
     */
    public function getNextJobRow(): ?HaystackBale
    {
        return $this->bales()->first();
    }

    /**
     * Get the next job from the Haystack.
     *
     * @return NextJob|null
     */
    public function getNextJob(): ?NextJob
    {
        $jobRow = $this->getNextJobRow();

        if (! $jobRow instanceof HaystackBale) {
            return null;
        }

        // We'll retrieve the configured job which will have
        // the delay, queue and connection all set up.

        $job = $jobRow->configuredJob();

        // We'll now set the Haystack model on the job.

        $job->setHaystack($this);

        // We'll now apply any global middleware if it was provided to us
        // while building the Haystack.

        if (filled($this->middleware)) {
            $middleware = call_user_func($this->middleware);

            if (is_array($middleware)) {
                $job->middleware = array_merge($job->middleware, $middleware);
            }
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
        if ($this->started === false) {
            $this->start();
            return;
        }

        $nextJob = $this->getNextJob();

        if (! $nextJob instanceof NextJob) {
            $this->finish();
            return;
        }

        $nextJob->haystackRow->delete();

        dispatch($nextJob->job);
    }

    /**
     * Start the Haystack.
     *
     * @return void
     */
    public function start(): void
    {
        $this->update(['started' => true]);

        $this->dispatchNextJob();
    }

    /**
     * Finish the Haystack.
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
     * Fail the Haystack.
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
        $pendingJob = CreatePendingHaystackBale::execute($job, $delayInSeconds, $queue, $connection);

        $this->appendPendingJob($pendingJob);
    }

    /**
     * Append the pending job to the Haystack.
     *
     * @param  PendingHaystackBale  $pendingJob
     * @return void
     */
    public function appendPendingJob(PendingHaystackBale $pendingJob): void
    {
        $this->bales()->create([
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
