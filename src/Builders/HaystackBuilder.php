<?php

namespace Sammyjo20\LaravelHaystack\Builders;

use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Sammyjo20\LaravelHaystack\Actions\CreatePendingHaystackBale;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Helpers\ClosureHelper;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class HaystackBuilder
{
    /**
     * Closure to run when the Haystack is finished.
     *
     * @var Closure|null
     */
    protected ?Closure $onThen = null;

    /**
     * Closure to run when the Haystack has failed.
     *
     * @var Closure|null
     */
    protected ?Closure $onCatch = null;

    /**
     * Closure to run when the Haystack has finished.
     *
     * @var Closure|null
     */
    protected ?Closure $onFinally = null;

    /**
     * The jobs to be added to the Haystack.
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
     * Global middleware.
     *
     * @var Closure|null
     */
    protected ?Closure $globalMiddleware = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->jobs = new Collection;
    }

    /**
     * Provide a closure that will run when the haystack is complete.
     *
     * @param Closure|callable $closure
     * @return $this
     */
    public function then(Closure|callable $closure): static
    {
        $this->onThen = ClosureHelper::fromCallable($closure);

        return $this;
    }

    /**
     * Provide a closure that will run when the haystack fails.
     *
     * @param Closure|callable $closure
     * @return $this
     */
    public function catch(Closure|callable $closure): static
    {
        $this->onCatch = ClosureHelper::fromCallable($closure);

        return $this;
    }

    /**
     * Provide a closure that will run when the haystack finishes.
     *
     * @param Closure|callable $closure
     * @return $this
     */
    public function finally(Closure|callable $closure): static
    {
        $this->onFinally = ClosureHelper::fromCallable($closure);

        return $this;
    }

    /**
     * Add a job to the haystack.
     *
     * @param ShouldQueue $job
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return $this
     */
    public function addJob(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        $pendingHaystackRow = CreatePendingHaystackBale::execute($job, $delayInSeconds, $queue, $connection);

        $this->jobs->add($pendingHaystackRow);

        return $this;
    }

    /**
     * Add a bale onto the haystack. Yee-haw!
     *
     * @alias addJob()
     *
     * @param ShouldQueue $job
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return $this
     */
    public function addBale(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        return $this->addJob($job, $delayInSeconds, $queue, $connection);
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
     * Set a global middleware closure to run.
     *
     * @param Closure|callable|array $value
     * @return $this
     */
    public function withMiddleware(Closure|callable|array $value): static
    {
        if (is_array($value)) {
            $value = static fn() => $value;
        }

        $this->globalMiddleware = ClosureHelper::fromCallable($value);

        return $this;
    }

    /**
     * Create the Haystack
     *
     * @return Haystack
     */
    public function create(): Haystack
    {
        return DB::transaction(fn() => $this->createHaystack());
    }

    /**
     * Dispatch the Haystack.
     *
     * @return Haystack
     */
    public function dispatch(): Haystack
    {
        $haystack = $this->create();

        $haystack->start();

        return $haystack;
    }

    /**
     * Map the jobs to be ready for inserting.
     *
     * @param Haystack $haystack
     * @return array
     */
    protected function prepareJobsForInsert(Haystack $haystack): array
    {
        return $this->jobs->map(function (PendingHaystackBale $pendingJob) use ($haystack) {
            $hasDelay = isset($pendingJob->delayInSeconds) && $pendingJob->delayInSeconds > 0;

            return [
                'haystack_id' => $haystack->getKey(),
                'job' => serialize($pendingJob->job),
                'delay' => $hasDelay ? $pendingJob->delayInSeconds : $this->globalDelayInSeconds,
                'on_queue' => $pendingJob->queue ?? $this->globalQueue,
                'on_connection' => $pendingJob->connection ?? $this->globalConnection,
            ];
        })->toArray();
    }

    /**
     * Create the haystack.
     *
     * @return Haystack
     */
    protected function createHaystack(): Haystack
    {
        $haystack = new Haystack;
        $haystack->on_then = $this->onThen;
        $haystack->on_catch = $this->onCatch;
        $haystack->on_finally = $this->onFinally;
        $haystack->middleware = $this->globalMiddleware;
        $haystack->save();

        $haystack->bales()->insert($this->prepareJobsForInsert($haystack));

        return $haystack;
    }

    /**
     * Get all the jobs in the builder.
     *
     * @return Collection
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    /**
     * Get the closure for the "onThen".
     *
     * @return Closure|null
     */
    public function getOnThen(): ?Closure
    {
        return $this->onThen;
    }

    /**
     * Get the closure for the "onCatch".
     *
     * @return Closure|null
     */
    public function getOnCatch(): ?Closure
    {
        return $this->onCatch;
    }

    /**
     * Get the closure for the "onFinally".
     *
     * @return Closure|null
     */
    public function getOnFinally(): ?Closure
    {
        return $this->onFinally;
    }

    /**
     * Get the time for the "withDelay".
     *
     * @return int
     */
    public function getGlobalDelayInSeconds(): int
    {
        return $this->globalDelayInSeconds;
    }

    /**
     * Get the global queue
     *
     * @return string|null
     */
    public function getGlobalQueue(): ?string
    {
        return $this->globalQueue;
    }

    /**
     * Get the global connection.
     *
     * @return string|null
     */
    public function getGlobalConnection(): ?string
    {
        return $this->globalConnection;
    }

    /**
     * Get the closure for the global middleware.
     *
     * @return Closure|null
     */
    public function getGlobalMiddleware(): ?Closure
    {
        return $this->globalMiddleware;
    }
}
