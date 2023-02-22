<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Concerns;

use Closure;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Sammyjo20\LaravelHaystack\Data\NextJob;
use Sammyjo20\LaravelHaystack\Enums\FinishStatus;
use Sammyjo20\LaravelHaystack\Helpers\DataHelper;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Models\HaystackData;
use Sammyjo20\LaravelHaystack\Helpers\CarbonHelper;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Casts\SerializedModel;
use Sammyjo20\LaravelHaystack\Helpers\DataValidator;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Data\CallbackCollection;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Middleware\CheckAttempts;
use Sammyjo20\LaravelHaystack\Middleware\CheckFinished;
use Sammyjo20\LaravelHaystack\Data\MiddlewareCollection;
use Sammyjo20\LaravelHaystack\Middleware\IncrementAttempts;
use Sammyjo20\LaravelHaystack\Exceptions\HaystackModelExists;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

trait ManagesBales
{
    /**
     * Get the next job row in the Haystack.
     */
    public function getNextJobRow(): ?HaystackBale
    {
        return $this->bales()->first();
    }

    /**
     * Get the next job from the Haystack.
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
            ->setHaystackBaleId($jobRow->getKey())
            ->setHaystackBaleAttempts($jobRow->attempts);

        // We'll now apply any global middleware if it was provided to us
        // while building the Haystack.

        if ($this->middleware instanceof MiddlewareCollection) {
            $job->middleware = array_merge($job->middleware, $this->middleware->toMiddlewareArray());
        }

        // Apply default middleware. We'll need to make sure that
        // the job middleware is added to the top of the array.

        $defaultMiddleware = [
            new CheckFinished,
            new CheckAttempts,
            new IncrementAttempts,
        ];

        $job->middleware = array_merge($defaultMiddleware, $job->middleware);

        // Return the NextJob DTO which contains the job and the row!

        return new NextJob($job, $jobRow);
    }

    /**
     * Dispatch the next job.
     *
     *
     * @throws PhpVersionNotSupportedException
     */
    public function dispatchNextJob(StackableJob $currentJob = null, int|CarbonInterface $delayInSecondsOrCarbon = null): void
    {
        // If the resume_at has been set, and the date is in the future, we're not allowed to process
        // the next job, so we stop.

        if ($this->resume_at instanceof CarbonInterface && $this->resume_at->isFuture()) {
            return;
        }

        if (is_null($currentJob) && $this->started === false) {
            $this->start();

            return;
        }

        // If the job has been provided, we will delete the haystack bale to prevent
        // the same bale being retrieved on the next job.

        if (isset($currentJob)) {
            HaystackBale::query()->whereKey($currentJob->getHaystackBaleId())->delete();
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
     *
     * @throws PhpVersionNotSupportedException
     */
    public function start(): void
    {
        $this->update(['started_at' => now()]);

        $this->dispatchNextJob();
    }

    /**
     * Restart the haystack
     *
     *
     * @throws PhpVersionNotSupportedException
     */
    public function restart(): void
    {
        $this->dispatchNextJob();
    }

    /**
     * Cancel the haystack.
     *
     *
     * @throws PhpVersionNotSupportedException
     */
    public function cancel(): void
    {
        $this->finish(FinishStatus::Cancelled);
    }

    /**
     * Finish the Haystack.
     *
     *
     * @throws PhpVersionNotSupportedException
     */
    public function finish(FinishStatus $status = FinishStatus::Success): void
    {
        if ($this->finished === true) {
            return;
        }

        $this->update(['finished_at' => now()]);

        $callbacks = $this->getCallbacks();

        $data = $callbacks->isNotEmpty() ? $this->conditionallyGetAllData() : null;

        match ($status) {
            FinishStatus::Success => $this->invokeCallbacks($callbacks->onThen, $data),
            FinishStatus::Failure => $this->invokeCallbacks($callbacks->onCatch, $data),
            default => null,
        };

        // Always execute the finally closure.

        $this->invokeCallbacks($callbacks->onFinally, $data);

        // Now finally delete itself.

        if (config('haystack.delete_finished_haystacks') === true) {
            $this->delete();
        }
    }

    /**
     * Fail the Haystack.
     *
     *
     * @throws PhpVersionNotSupportedException
     */
    public function fail(): void
    {
        $this->finish(FinishStatus::Failure);
    }

    /**
     * Add new jobs to the haystack.
     */
    public function addJobs(StackableJob|Collection|array $jobs, int $delayInSeconds = 0, string $queue = null, string $connection = null, bool $prepend = false): void
    {
        if ($jobs instanceof StackableJob) {
            $jobs = [$jobs];
        }

        if ($jobs instanceof Collection) {
            $jobs = $jobs->all();
        }

        $pendingJobs = [];

        foreach ($jobs as $job) {
            $pendingJobs[] = new PendingHaystackBale($job, $delayInSeconds, $queue, $connection, $prepend);
        }

        $this->addPendingJobs($pendingJobs);
    }

    /**
     * Add pending jobs to the haystack.
     */
    public function addPendingJobs(array $pendingJobs): void
    {
        $pendingJobRows = collect($pendingJobs)
            ->filter(fn ($pendingJob) => $pendingJob instanceof PendingHaystackBale)
            ->map(fn (PendingHaystackBale $pendingJob) => $pendingJob->toDatabaseRow($this))
            ->all();

        if (empty($pendingJobRows)) {
            return;
        }

        $this->bales()->insert($pendingJobRows);
    }

    /**
     * Execute the closures.
     *
     * @param  array<SerializableClosure>  $closures
     *
     * @throws PhpVersionNotSupportedException
     */
    protected function invokeCallbacks(?array $closures, ?Collection $data = null): void
    {
        collect($closures)->each(function (SerializableClosure $closure) use ($data) {
            $closure($data);
        });
    }

    /**
     * Pause the haystack.
     *
     *
     * @throws PhpVersionNotSupportedException
     */
    public function pause(CarbonImmutable $resumeAt): void
    {
        $this->update(['resume_at' => $resumeAt]);

        $callbacks = $this->getCallbacks();

        if (empty($callbacks->onPaused)) {
            return;
        }

        $data = $this->conditionallyGetAllData();

        $this->invokeCallbacks($callbacks->onPaused, $data);
    }

    /**
     * Store data on the Haystack.
     *
     * @return ManagesBales|\Sammyjo20\LaravelHaystack\Models\Haystack
     */
    public function setData(string $key, mixed $value, string $cast = null): self
    {
        DataValidator::validateCast($value, $cast);

        $this->data()->updateOrCreate(['key' => $key], [
            'cast' => $cast,
            'value' => $value,
        ]);

        return $this;
    }

    /**
     * Retrieve data by a key from the Haystack.
     */
    public function getData(string $key, mixed $default = null): mixed
    {
        $data = $this->data()->where('key', $key)->first();

        return $data instanceof HaystackData ? $data->value : $default;
    }

    /**
     * Retrieve a shared model
     */
    public function getModel(string $key, mixed $default = null): mixed
    {
        return $this->getData('model:'.$key, $default);
    }

    /**
     * Set a model on a Haystack
     *
     * @return $this
     *
     * @throws HaystackModelExists
     */
    public function setModel(Model $model, string $key = null): static
    {
        $key = DataHelper::getModelKey($model, $key);

        if ($this->data()->where('key', $key)->exists()) {
            throw new HaystackModelExists($key);
        }

        return $this->setData($key, $model, SerializedModel::class);
    }

    /**
     * Retrieve all the data from the Haystack.
     */
    public function allData(bool $includeModels = false): Collection
    {
        $data = $this->data()
            ->when($includeModels === false, fn ($query) => $query->where('key', 'NOT LIKE', 'model:%'))
            ->orderBy('id')->get();

        return $data->mapWithKeys(function ($value, $key) {
            return [$value->key => $value->value];
        });
    }

    /**
     * Conditionally retrieve all the data from the Haystack depending on
     * if we are able to return the data.
     */
    protected function conditionallyGetAllData(): ?Collection
    {
        $returnAllData = config('haystack.return_all_haystack_data_when_finished', false);

        return $this->options->returnDataOnFinish === true && $returnAllData === true ? $this->allData() : null;
    }

    /**
     * Increment the bale attempts.
     */
    public function incrementBaleAttempts(StackableJob $job): void
    {
        HaystackBale::query()->whereKey($job->getHaystackBaleId())->increment('attempts');
    }

    /**
     * Get the callbacks on the Haystack.
     */
    public function getCallbacks(): CallbackCollection
    {
        return $this->callbacks ?? new CallbackCollection;
    }
}
