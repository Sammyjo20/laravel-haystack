<?php

namespace Sammyjo20\LaravelJobStack\Builders;

use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Sammyjo20\LaravelJobStack\Actions\CreatePendingJobStackRow;
use Sammyjo20\LaravelJobStack\Concerns\Stackable;
use Sammyjo20\LaravelJobStack\Data\PendingJobStackRow;
use Sammyjo20\LaravelJobStack\Models\JobStack;

class JobStackBuilder
{
    /**
     * Closure to run when the JobStack is finished.
     *
     * @var Closure|null
     */
    protected ?Closure $onThen = null;

    /**
     * Closure to run when the JobStack has failed.
     *
     * @var Closure|null
     */
    protected ?Closure $onCatch = null;

    /**
     * Closure to run when the JobStack has finished.
     *
     * @var Closure|null
     */
    protected ?Closure $onFinally = null;

    /**
     * The jobs to be added to the JobStack.
     *
     * @var Collection
     */
    protected Collection $jobs;

    /**
     * Global delay in seconds.
     *
     * @var int
     */
    protected int $globalDelayInSeconds = 0;

    /**
     * Global queue.
     *
     * @var string|null
     */
    protected ?string $globalQueue = null;

    /**
     * Global connection.
     *
     * @var string|null
     */
    protected ?string $globalConnection = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->jobs = new Collection;
    }

    /**
     * Normalize the closure.
     *
     * @param Closure|callable $callable
     * @return Closure
     */
    protected function normalizeClosure(Closure|callable $callable): Closure
    {
        return $callable instanceof Closure ? $callable : static fn() => $callable();
    }

    /**
     * Map the jobs to be ready for inserting.
     *
     * @param JobStack $jobStack
     * @return array
     */
    protected function prepareJobsForInsert(JobStack $jobStack): array
    {
        return $this->jobs->map(function (PendingJobStackRow $pendingJob) use ($jobStack) {
            return [
                'job_stack_id' => $jobStack->getKey(),
                'job' => serialize($pendingJob->job),
                'delay' => $pendingJob->delayInSeconds ?? $this->globalDelayInSeconds,
                'on_queue' => $pendingJob->queue ?? $this->globalQueue,
                'on_connection' => $pendingJob->connection ?? $this->globalConnection,
            ];
        })->toArray();
    }

    /**
     * Create the job stack.
     *
     * @return JobStack
     */
    protected function createJobStack(): JobStack
    {
        $jobStack = new JobStack;
        $jobStack->on_then = $this->onThen;
        $jobStack->on_catch = $this->onCatch;
        $jobStack->on_finally = $this->onFinally;
        $jobStack->save();

        $jobStack->rows()->insert($this->prepareJobsForInsert($jobStack));

        return $jobStack;
    }

    /**
     * Provide a closure that will run when the job stack is complete.
     *
     * @param Closure|callable $closure
     * @return $this
     */
    public function then(Closure|callable $closure): static
    {
        $this->onThen = $this->normalizeClosure($closure);

        return $this;
    }

    /**
     * Provide a closure that will run when the job stack fails.
     *
     * @param Closure|callable $closure
     * @return $this
     */
    public function catch(Closure|callable $closure): static
    {
        $this->onCatch = $this->normalizeClosure($closure);

        return $this;
    }

    /**
     * Provide a closure that will run when the job stack finishes.
     *
     * @param Closure|callable $closure
     * @return $this
     */
    public function finally(Closure|callable $closure): static
    {
        $this->onFinally = $this->normalizeClosure($closure);

        return $this;
    }

    /**
     * Add a job to the job stack.
     *
     * @param ShouldQueue $job
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return $this
     */
    public function addJob(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        $pendingJobStackRow = CreatePendingJobStackRow::execute($job, $delayInSeconds, $queue, $connection);

        $this->jobs->add($pendingJobStackRow);

        return $this;
    }

    /**
     * Set a global delay on the jobs.
     *
     * @param int $seconds
     * @return $this
     */
    public function withDelay(int $seconds): static
    {
        $this->globalDelayInSeconds = $seconds;

        return $this;
    }

    /**
     * Set a global queue for the jobs.
     *
     * @param string $queue
     * @return $this
     */
    public function onQueue(string $queue): static
    {
        $this->globalQueue = $queue;

        return $this;
    }

    /**
     * Set a global connection for the jobs.
     *
     * @param string $connection
     * @return $this
     */
    public function onConnection(string $connection): static
    {
        $this->globalConnection = $connection;

        return $this;
    }

    /**
     * Dispatch the JobStack.
     *
     * @return JobStack
     */
    public function dispatch(): JobStack
    {
        /** @var JobStack $jobStack */
        $jobStack = DB::transaction(fn () => $this->createJobStack());

        $jobStack->start();

        return $jobStack;
    }
}
