<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Builders;

use Closure;
use Grpc\Call;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Conditionable;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Data\CallbackCollection;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Data\PendingData;
use Sammyjo20\LaravelHaystack\Data\HaystackOptions;
use Sammyjo20\LaravelHaystack\Casts\SerializedModel;
use Sammyjo20\LaravelHaystack\Helpers\ClosureHelper;
use Sammyjo20\LaravelHaystack\Helpers\DataValidator;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Exceptions\HaystackModelExists;
use Sammyjo20\LaravelHaystack\Data\MiddlewareCollection;

class HaystackBuilder
{
    use Conditionable;

    /**
     * The name of the haystack.
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The jobs to be added to the Haystack.
     *
     * @var Collection
     */
    protected Collection $jobs;

    /**
     * Global connection
     *
     * @var string|null
     */
    public ?string $globalConnection = null;

    /**
     * Global queue
     *
     * @var string|null
     */
    public ?string $globalQueue = null;

    /**
     * Global delay
     *
     * @var int
     */
    public int $globalDelayInSeconds = 0;

    /**
     * Callbacks that will be run at various events
     *
     * @var CallbackCollection
     */
    protected CallbackCollection $callbacks;

    /**
     * Middleware that will be applied to every job
     *
     * @var MiddlewareCollection
     */
    protected MiddlewareCollection $middleware;

    /**
     * Other Haystack Options
     *
     * @var HaystackOptions
     */
    protected HaystackOptions $options;

    /**
     * Array of pending data objects containing the initial data.
     *
     * @var Collection
     */
    protected Collection $initialData;

    /**
     * Closure to execute before saving
     *
     * @var Closure|null
     */
    protected ?Closure $beforeSave = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->jobs = new Collection;
        $this->initialData = new Collection;
        $this->callbacks = new CallbackCollection;
        $this->options = new HaystackOptions;
        $this->middleware = new MiddlewareCollection;
    }

    /**
     * Specify a name for the haystack.
     *
     * @param string $name
     * @return $this
     */
    public function withName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Provide a closure that will run when the haystack is complete.
     *
     * @param Closure|callable $closure
     * @return $this
     * @throws PhpVersionNotSupportedException
     */
    public function then(Closure|callable $closure): static
    {
        $this->callbacks->addThen($closure);

        return $this;
    }

    /**
     * Provide a closure that will run when the haystack fails.
     *
     * @param Closure|callable $closure
     * @return $this
     * @throws PhpVersionNotSupportedException
     */
    public function catch(Closure|callable $closure): static
    {
        $this->callbacks->addCatch($closure);

        return $this;
    }

    /**
     * Provide a closure that will run when the haystack finishes.
     *
     * @param Closure|callable $closure
     * @return $this
     * @throws PhpVersionNotSupportedException
     */
    public function finally(Closure|callable $closure): static
    {
        $this->callbacks->addFinally($closure);

        return $this;
    }

    /**
     * Provide a closure that will run when the haystack is paused.
     *
     * @param Closure|callable $closure
     * @return $this
     * @throws PhpVersionNotSupportedException
     */
    public function paused(Closure|callable $closure): static
    {
        $this->callbacks->addPaused($closure);

        return $this;
    }

    /**
     * Add a job to the haystack.
     *
     * @param StackableJob $job
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return $this
     */
    public function addJob(StackableJob $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        $pendingHaystackRow = new PendingHaystackBale($job, $delayInSeconds, $queue, $connection);

        $this->jobs->add($pendingHaystackRow);

        return $this;
    }

    /**
     * Add a job when a condition is true.
     *
     * @param bool $condition
     * @param ...$arguments
     * @return $this
     */
    public function addJobWhen(bool $condition, ...$arguments): static
    {
        return $condition === true ? $this->addJob(...$arguments) : $this;
    }

    /**
     * Add a job when a condition is false.
     *
     * @param bool $condition
     * @param ...$arguments
     * @return $this
     */
    public function addJobUnless(bool $condition, ...$arguments): static
    {
        return $this->addJobWhen(! $condition, ...$arguments);
    }

    /**
     * Add multiple jobs to the haystack at a time.
     *
     * @param Collection|array $jobs
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return $this
     */
    public function addJobs(Collection|array $jobs, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        if (is_array($jobs)) {
            $jobs = collect($jobs);
        }

        $jobs = $jobs->filter(fn($job) => $job instanceof StackableJob);

        foreach ($jobs as $job) {
            $this->addJob($job, $delayInSeconds, $queue, $connection);
        }

        return $this;
    }

    /**
     * Add jobs when a condition is true.
     *
     * @param bool $condition
     * @param ...$arguments
     * @return $this
     */
    public function addJobsWhen(bool $condition, ...$arguments): static
    {
        return $condition === true ? $this->addJobs(...$arguments) : $this;
    }

    /**
     * Add jobs when a condition is false.
     *
     * @param bool $condition
     * @param ...$arguments
     * @return $this
     */
    public function addJobsUnless(bool $condition, ...$arguments): static
    {
        return $this->addJobsWhen(! $condition, ...$arguments);
    }

    /**
     * Add a bale onto the haystack. Yee-haw!
     *
     * @alias addJob()
     *
     * @param StackableJob $job
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return $this
     */
    public function addBale(StackableJob $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        return $this->addJob($job, $delayInSeconds, $queue, $connection);
    }

    /**
     * Add multiple bales onto the haystack. Yee-haw!
     *
     * @alias addJobs()
     *
     * @param Collection|array $jobs
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return $this
     */
    public function addBales(Collection|array $jobs, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        return $this->addJobs($jobs, $delayInSeconds, $queue, $connection);
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
     * Add some middleware to be merged in with every job
     *
     * @param Closure|callable|array $value
     * @return $this
     * @throws PhpVersionNotSupportedException
     */
    public function addMiddleware(Closure|callable|array $value): static
    {
        $this->middleware->add($value);

        return $this;
    }

    /**
     * Provide data before the haystack is created.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $cast
     * @return $this
     */
    public function withData(string $key, mixed $value, string $cast = null): static
    {
        DataValidator::validateCast($value, $cast);

        $this->initialData->put($key, new PendingData($key, $value, $cast));

        return $this;
    }

    /**
     * Store a model to be shared across all haystack jobs.
     *
     * @param Model $model
     * @param string|null $key
     * @return $this
     *
     * @throws HaystackModelExists
     */
    public function withModel(Model $model, string $key = null): static
    {
        $key = 'model:' . ($key ?? $model::class);

        if ($this->initialData->has($key)) {
            throw new HaystackModelExists($key);
        }

        $this->initialData->put($key, new PendingData($key, $model, SerializedModel::class));

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
        $now = Carbon::now();

        $timestamps = [
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return $this->jobs->map(function (PendingHaystackBale $pendingJob) use ($haystack, $timestamps) {
            $hasDelay = isset($pendingJob->delayInSeconds) && $pendingJob->delayInSeconds > 0;

            // We'll create a dummy Haystack bale model for each row
            // and convert it into its attributes just for the casting.

            $baseAttributes = $haystack->bales()->make([
                'job' => $pendingJob->job,
                'delay' => $hasDelay ? $pendingJob->delayInSeconds : $this->globalDelayInSeconds,
                'on_queue' => $pendingJob->queue ?? $this->globalQueue,
                'on_connection' => $pendingJob->connection ?? $this->globalConnection,
            ])->getAttributes();

            // Next we'll merge in the timestamps

            return array_merge($timestamps, $baseAttributes);
        })->toArray();
    }

    /**
     * Map the initial data to be ready for inserting.
     *
     * @param Haystack $haystack
     * @return array
     */
    protected function prepareDataForInsert(Haystack $haystack): array
    {
        return $this->initialData->map(function (PendingData $pendingData) use ($haystack) {
            // We'll create a dummy Haystack data model for each row
            // and convert it into its attributes just for the casting.

            return $haystack->data()->make([
                'key' => $pendingData->key,
                'cast' => $pendingData->cast,
                'value' => $pendingData->value,
            ])->getAttributes();
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
        $haystack->name = $this->name;
        $haystack->callbacks = $this->callbacks->toSerializable();
        $haystack->middleware = $this->middleware->toSerializable();
        $haystack->options = $this->options;

        if ($this->beforeSave instanceof Closure) {
            $haystack = tap($haystack, $this->beforeSave);
        }

        $haystack->save();

        // We'll bulk insert the jobs and the data for efficient querying.

        if ($this->jobs->isNotEmpty()) {
            $haystack->bales()->insert($this->prepareJobsForInsert($haystack));
        }

        if ($this->initialData->isNotEmpty()) {
            $haystack->data()->insert($this->prepareDataForInsert($haystack));
        }

        return $haystack;
    }

    /**
     * Specify if you do not want haystack to return the data.
     *
     * @return $this
     */
    public function dontReturnData(): static
    {
        $this->options->returnDataOnFinish = false;

        return $this;
    }

    /**
     * Allow failures on the Haystack
     *
     * @return $this
     */
    public function allowFailures(): static
    {
        $this->options->allowFailures = true;

        return $this;
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
     * Retrieve the callbacks
     *
     * @return CallbackCollection
     */
    public function getCallbacks(): CallbackCollection
    {
        return $this->callbacks;
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
     * @return MiddlewareCollection
     */
    public function getMiddleware(): MiddlewareCollection
    {
        return $this->middleware;
    }

    /**
     * Specify a closure to run before saving the Haystack
     *
     * @param Closure $closure
     * @return $this
     */
    public function beforeSave(Closure $closure): static
    {
        $this->beforeSave = $closure;

        return $this;
    }

    /**
     * Set an option on the Haystack Options.
     *
     * @param string $option
     * @param mixed $value
     * @return $this
     */
    public function setOption(string $option, mixed $value): static
    {
        $this->options->$option = $value;

        return $this;
    }
}
