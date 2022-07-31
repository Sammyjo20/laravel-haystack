<?php

namespace Sammyjo20\LaravelHaystack\Concerns;

use Closure;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelHaystack\Data\NextJob;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Helpers\CarbonHelper;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Actions\CreatePendingHaystackBale;

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

        $job->setHaystack($this)
            ->setHaystackBaleId($jobRow->getKey());

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
     * @param  StackableJob|null  $job
     * @param  int|CarbonInterface|null  $delayInSecondsOrCarbon
     * @return void
     */
    public function dispatchNextJob(StackableJob $job = null, int|CarbonInterface $delayInSecondsOrCarbon = null): void
    {
        // If the resume_at has been set, and the date is in the future, we're not allowed to process
        // the next job, so we stop.

        if ($this->resume_at instanceof CarbonInterface && $this->resume_at->isFuture()) {
            return;
        }

        if (is_null($job) && $this->started === false) {
            $this->start();

            return;
        }

        // If the job has been provided, we will delete the haystack bale to prevent
        // the same bale being retrieved on the next job.

        if (isset($job)) {
            HaystackBale::query()->whereKey($job->getHaystackBaleId())->delete();
        }

        // If the delay in seconds has been provided, we need to pause the haystack by the
        // delay.

        if (isset($delayInSecondsOrCarbon)) {
            $this->pause(CarbonHelper::createFromSecondsOrCarbon($delayInSecondsOrCarbon));

            return;
        }

        // Now we'll query the next bale.

        $nextJob = $this->getNextJob();

        // If no next job was found, we'll stop.

        if (! $nextJob instanceof NextJob) {
            $this->finish();

            return;
        }

        dispatch($nextJob->job);
    }

    /**
     * Start the Haystack.
     *
     * @return void
     */
    public function start(): void
    {
        $this->update(['started' => true, 'started_at' => now()]);

        $this->dispatchNextJob();
    }

    /**
     * Restart the haystack
     *
     * @return void
     */
    public function restart(): void
    {
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

        $this->update(['finished' => true, 'finished_at' => now()]);

        $fail === true
            ? $this->executeClosure($this->on_catch)
            : $this->executeClosure($this->on_then);

        // Always execute the finally closure.

        $this->executeClosure($this->on_finally);

        // Now finally delete itself.

        if (config('haystack.delete_finished_haystacks') === true) {
            $this->delete();
        }
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

    /**
     * Pause the haystack.
     *
     * @param  CarbonImmutable  $resumeAt
     * @return void
     */
    public function pause(CarbonImmutable $resumeAt): void
    {
        $this->update(['resume_at' => $resumeAt]);
    }
}
