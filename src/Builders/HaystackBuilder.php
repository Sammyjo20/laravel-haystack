<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Builders;

use Closure;
use DateTimeInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Conditionable;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Data\PendingData;
use Sammyjo20\LaravelHaystack\Helpers\DataHelper;
use Sammyjo20\LaravelHaystack\Data\HaystackOptions;
use Sammyjo20\LaravelHaystack\Casts\SerializedModel;
use Sammyjo20\LaravelHaystack\Helpers\DataValidator;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Data\CallbackCollection;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Data\MiddlewareCollection;
use Sammyjo20\LaravelHaystack\Exceptions\HaystackModelExists;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

class HaystackBuilder
{
    use Conditionable;

    /**
     * The name of the haystack.
     */
    protected ?string $name = null;

    /**
     * The jobs to be added to the Haystack.
     */
    protected Collection $jobs;

    /**
     * Global connection
     */
    public ?string $globalConnection = null;

    /**
     * Global queue
     */
    public ?string $globalQueue = null;

    /**
     * Global delay
     */
    public int $globalDelayInSeconds = 0;

    /**
     * Initial resumeAt time
     */
    public ?CarbonImmutable $resumeAt = null;

    /**
     * Callbacks that will be run at various events
     */
    protected CallbackCollection $callbacks;

    /**
     * Middleware that will be applied to every job
     */
    protected MiddlewareCollection $middleware;

    /**
     * Other Haystack Options
     */
    protected HaystackOptions $options;

    /**
     * Array of pending data objects containing the initial data.
     */
    protected Collection $initialData;

    /**
     * Closure to execute before saving
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
     * @return $this
     *
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
     * @return $this
     *
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
     * @return $this
     *
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
     * @return $this
     *
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
     * @return $this
     */
    public function addJob(StackableJob $job, int $delayInSeconds = 0, ?string $queue = null, ?string $connection = null): static
    {
        $pendingHaystackRow = new PendingHaystackBale($job, $delayInSeconds, $queue, $connection);

        $this->jobs->add($pendingHaystackRow);

        return $this;
    }

    /**
     * Add a job when a condition is true.
     *
     * @return $this
     */
    public function addJobWhen(bool $condition, ...$arguments): static
    {
        return $condition === true ? $this->addJob(...$arguments) : $this;
    }

    /**
     * Add a job when a condition is false.
     *
     * @return $this
     */
    public function addJobUnless(bool $condition, ...$arguments): static
    {
        return $this->addJobWhen(! $condition, ...$arguments);
    }

    /**
     * Add multiple jobs to the haystack at a time.
     *
     * @return $this
     */
    public function addJobs(Collection|array $jobs, int $delayInSeconds = 0, ?string $queue = null, ?string $connection = null): static
    {
        if (is_array($jobs)) {
            $jobs = collect($jobs);
        }

        $jobs = $jobs->filter(fn ($job) => $job instanceof StackableJob);

        foreach ($jobs as $job) {
            $this->addJob($job, $delayInSeconds, $queue, $connection);
        }

        return $this;
    }

    /**
     * Add jobs when a condition is true.
     *
     * @return $this
     */
    public function addJobsWhen(bool $condition, ...$arguments): static
    {
        return $condition === true ? $this->addJobs(...$arguments) : $this;
    }

    /**
     * Add jobs when a condition is false.
     *
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
     * @return $this
     */
    public function addBale(StackableJob $job, int $delayInSeconds = 0, ?string $queue = null, ?string $connection = null): static
    {
        return $this->addJob($job, $delayInSeconds, $queue, $connection);
    }

    /**
     * Add multiple bales onto the haystack. Yee-haw!
     *
     * @alias addJobs()
     *
     * @return $this
     */
    public function addBales(Collection|array $jobs, int $delayInSeconds = 0, ?string $queue = null, ?string $connection = null): static
    {
        return $this->addJobs($jobs, $delayInSeconds, $queue, $connection);
    }

    /**
     * Set a global delay on the jobs.
     *
     * @return $this
     */
    public function withDelay(int $seconds): static
    {
        $this->globalDelayInSeconds = $seconds;

        return $this;
    }

    public function pausedUntil(DateTimeInterface $resumeAt): static
    {
        if (! $resumeAt instanceof CarbonImmutable) {
            $resumeAt = Carbon::parse($resumeAt)->toImmutable();
        }

        $this->resumeAt = $resumeAt;

        return $this;
    }

    /**
     * Set a global queue for the jobs.
     *
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
     * @return $this
     *
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
     * @return $this
     */
    public function withData(string $key, mixed $value, ?string $cast = null): static
    {
        DataValidator::validateCast($value, $cast);

        $this->initialData->put($key, new PendingData($key, $value, $cast));

        return $this;
    }

    /**
     * Store a model to be shared across all haystack jobs.
     *
     * @return $this
     *
     * @throws HaystackModelExists
     */
    public function withModel(Model $model, ?string $key = null): static
    {
        $key = DataHelper::getModelKey($model, $key);

        if ($this->initialData->has($key)) {
            throw new HaystackModelExists($key);
        }

        $this->initialData->put($key, new PendingData($key, $model, SerializedModel::class));

        return $this;
    }

    /**
     * Create the Haystack
     */
    public function create(): Haystack
    {
        return DB::transaction(fn () => $this->createHaystack());
    }

    /**
     * Dispatch the Haystack.
     */
    public function dispatch(): Haystack
    {
        $haystack = $this->create();

        $haystack->start();

        return $haystack;
    }

    /**
     * Map the jobs to be ready for inserting.
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
     */
    protected function createHaystack(): Haystack
    {
        $haystack = new Haystack;
        $haystack->name = $this->name;
        $haystack->callbacks = $this->callbacks->toSerializable();
        $haystack->middleware = $this->middleware->toSerializable();
        $haystack->options = $this->options;
        $haystack->resume_at = $this->resumeAt;

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
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    /**
     * Retrieve the callbacks
     */
    public function getCallbacks(): CallbackCollection
    {
        return $this->callbacks;
    }

    /**
     * Get the time for the "withDelay".
     */
    public function getGlobalDelayInSeconds(): int
    {
        return $this->globalDelayInSeconds;
    }

    /**
     * Get the global queue
     */
    public function getGlobalQueue(): ?string
    {
        return $this->globalQueue;
    }

    /**
     * Get the global connection.
     */
    public function getGlobalConnection(): ?string
    {
        return $this->globalConnection;
    }

    /**
     * Get the closure for the global middleware.
     */
    public function getMiddleware(): MiddlewareCollection
    {
        return $this->middleware;
    }

    /**
     * Specify a closure to run before saving the Haystack
     *
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
     * @return $this
     */
    public function setOption(string $option, mixed $value): static
    {
        $this->options->$option = $value;

        return $this;
    }
}
